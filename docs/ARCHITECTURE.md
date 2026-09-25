# Architecture & design decisions

## Layout

```
app/
  Casts/MoneyCast.php            DECIMAL column <-> Money object
  Console/Commands/              wallet:reconcile, platform:create-admin
  DTOs/PriceQuote.php            server-computed pricing
  Enums/                         roles, statuses, transaction types, …
  Events/ Listeners/             TaskApproved, SubmissionCreated (fraud checks + requester notice), SubmissionReviewed
  Exceptions/BusinessRuleException.php   user-safe domain errors (rendered as flash message or JSON {message, code})
  Http/Controllers/{Admin,Developer,Requester,Api,Auth}
  Http/Middleware/               role, active (suspension), verified.required, maintenance, security headers
  Http/Requests/                 TaskRequest, BatchRequest, SubmitAnswerRequest, WithdrawalRequest, RegisterRequest
  Http/Resources/                API resources
  Jobs/                          scheduled: ExpireStaleClaims, SendClaimExpiryWarnings, CloseExpiredTasks, AutoApproveStaleSubmissions
  Models/
  Notifications/                 PlatformNotification base (database + optional mail, queued)
  Payments/                      gateway contracts + manual drivers
  Policies/                      Task, TaskClaim, TaskSubmission, Attachment
  Services/                      all business logic (see below)
  Support/Money.php              integer-cent money value object
  TaskTypes/                     pluggable task type handlers + registry
```

Controllers are thin: validate (Form Request) → authorize (Policy) → call a service → redirect/resource. Views contain no business logic.

## Services

| Service | Responsibility |
|---|---|
| `WalletService` | The **only** code allowed to change balances. `post()` locks the wallet row, updates the cached bucket balance and writes one immutable ledger row. `reconcile()` verifies caches against the ledger. |
| `TaskPricingService` | Reward → fee → unit cost → total, using the current commission. |
| `TaskService` | Create/update/approve/reject/suspend/pause/cancel tasks, batches, add slots, deadlines, slot and escrow primitives. |
| `TaskClaimService` | Claim (row-locked), release, drafts, expiry (scheduled + lazy), expiry warnings. |
| `TaskSubmissionService` | Server-side deadline check, submission creation, pending reward credit, attachments. |
| `SubmissionReviewService` | Approve / reject / request revision / admin overrides / auto-approval, developer stats & level. |
| `TaskRewardService` | Money movements for review decisions (payout, reversal, clawback). |
| `WithdrawalService`, `DepositService` | Payout and funding flows via gateway contracts. |
| `DisputeService` | Appeals and their resolution. |
| `FraudDetectionService` | Heuristic flags and fraud score. |
| `Analytics\*` | Dashboard figures. |
| `SettingsService` | Admin-editable runtime settings with config defaults (`config/platform.php`). |

## Money

* All amounts are `DECIMAL(10|14, 2)` in MySQL and `App\Support\Money` (integer cents) in PHP. Parsing and formatting use strings only; commission uses integer basis-point maths with half-up rounding (`0.35 × 30% = 0.105 → 0.11`).
* Clients never send prices that are trusted. `reward` per slot is the only monetary input a requester provides (bounded by settings); fee, totals and escrow are computed server-side, and the Task model does not make financial columns fillable.
* **Two buckets per wallet.** `balance` (available) and `pending_balance`. For developers, *pending* holds submitted rewards awaiting review; for requesters it is *escrow* for open task slots. The platform revenue wallet has `user_id = NULL`.
* **Immutable ledger.** `wallet_transactions` rows are append-only (the model throws on update/delete). Each has a signed `amount`, the `bucket`, `balance_after`, a polymorphic reference (task/submission/withdrawal/deposit) and the acting user. Corrections are new offsetting rows (refunds, adjustments, clawbacks).
* Cached balances always equal `SUM(amount)` per bucket; `php artisan wallet:reconcile` runs nightly and logs a critical error on mismatch.

### Money flow of one task slot (reward $0.50, commission 30%)

| Event | Requester | Developer | Platform |
|---|---|---|---|
| Task created | available −0.65, escrow +0.65 | | |
| Submission | | pending +0.50 | |
| Approved | escrow −0.65 (lifetime spending +0.65) | pending −0.50, available +0.50 | +0.15 |
| Rejected | (slot re-opens; escrow kept) | pending −0.50 | |
| Task closes with unused slot | escrow −0.65, available +0.65 (refund) | | |

## Task lifecycle

```
Requester creates ─► pending_approval ─► (admin) active ─► completed (all slots approved)
                            │                  │ ├─► paused ◄─► active (requester)
                            ▼                  │ ├─► suspended ◄─► active (admin)
                         rejected (refund)     │ └─► cancelled / expired (deadline) → unused escrow refunded
                            │ edit & resubmit  │
                            └──────────────────┘
Developer: claim (slot reserved, timer) ─► submit ─► pending review ─► approved (paid)
                │                                       ├─► rejected (slot re-opens, appeal possible)
                ├─► release / expired (slot freed)      └─► revision requested (claim re-opened with new timer)
```

* **Slots.** `available_slots` (purchased), `reserved_slots` (active claims + pending submissions), `completed_slots` (approved). Claimable when `available > reserved + completed`. All three change only under a row lock on the task.
* **No duplicate claiming.** Unique index `(task_id, developer_id)` plus an in-transaction check. A developer can work on a given task once, ever (batches create separate tasks, so a developer can do many items of a batch). Default max one active claim per developer (setting).
* **Timer.** `claimed_at`/`expires_at` are set by the server; allowed time = `max(claim_min_minutes, ceil(estimate × claim_duration_multiplier))`. Submissions are accepted until `expires_at + claim_grace_seconds`. The browser countdown corrects for clock skew using the server time but is only a display.
* **Expiry.** A scheduled job expires overdue claims every minute, and expiry also runs lazily whenever anyone tries to claim that task or opens the workspace, so correctness doesn't depend on cron. Policy `expired_claim_policy`: `expire` (default, discard) or `submit_draft` (auto-submit the autosaved draft if it passes validation).
* **Review protection.** Pending submissions older than `auto_approve_days` are auto-approved unless flagged by fraud checks.
* **Overrides.** Admin can approve a rejected submission (re-reserving a slot, or funding an extra slot from the requester if the task is full) or reverse an approval (clawback from developer, commission reversal, requester refund).

## Task types

A task type implements `App\TaskTypes\TaskTypeHandler` (usually by extending `AbstractTaskType`) and provides:

* `payloadRules()` / `normalizePayload()` — requester content (e.g. "one per line" → array),
* `answerRules()` / `normalizeAnswer()` / `prepareAnswer()` — developer answer validation and whitelisting,
* `fingerprint()` — free text used for duplicate detection (or `null`),
* `batchField()` — which payload field a plain batch line fills,
* four partials in `resources/views/task-types/{key}/`: `form`, `content`, `answer`, `review`.

Register the class in `config/tasktypes.php`. No other code changes are needed.

## Security

CSRF on all web forms; Blade auto-escaping (no `{!! !!}` with user data); Eloquent/query builder bindings only; policies + role middleware on every route group; bcrypt passwords; login/registration/claim/submit/withdraw/report/API rate limits; suspended users are signed out and their API tokens revoked; payout account details encrypted at rest (`encrypted` cast) and every admin view of them is audited; uploads validated by extension **and** sniffed MIME type and size, stored on the private disk under random names, downloaded only through an authorized controller (or a 15-minute signed URL for API clients) as `application/octet-stream`; security headers (nosniff, frame options, referrer policy, HSTS on HTTPS); secure/encrypted session settings in `.env.example`; `APP_DEBUG=false` in production shows custom error pages without stack traces; admin cannot be self-registered.

## Decisions made where the brief was open

| Topic | Decision |
|---|---|
| Laravel version | Laravel 12 (latest stable) on PHP 8.2+. |
| Requester funding | Prepaid balance + escrow. Deposits are reported by the requester and confirmed by an admin (manual gateway). |
| Pending balance | Developers see submitted-but-unreviewed rewards as pending; requesters see escrow as pending. |
| Claim duration | 2 × estimate, minimum 5 min, 60 s grace — all configurable. |
| Re-claiming | Not allowed after release/expiry to prevent gaming the timer; batches give developers plenty of similar items. |
| Rejections | Re-open the slot; developer can appeal for 14 days. |
| Auto-approval | 3 days, excludes fraud-flagged submissions. |
| Revision requests | Up to 2 per claim; reward moves back out of pending until resubmitted. |
| Fraud | Flags only; admin decides (confirm/dismiss/suspend). |
| Email verification | Required (toggle) to claim, post or withdraw — browsing is allowed. |
| Maintenance mode | Admin toggle; public pages, login and admin area stay up. |
| Tests DB | SQLite in-memory by default for portability; the suite also passes on MySQL. |
