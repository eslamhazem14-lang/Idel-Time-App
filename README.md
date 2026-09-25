# IdleTime — Developer Idle-Time Microtask Marketplace

> **Turn AI Waiting Time Into Money.** Developers complete small, paid technical tasks (2–15 min) while their AI coding agent (Claude Code, Cursor, Codex…) is working. Requesters get fast human technical validation. The platform takes a configurable commission.

Laravel 12 · PHP 8.2+ · MySQL · Blade + Tailwind CSS v4 + Alpine.js · Sanctum API.

---

## Quick start (local)

```bash
composer install
npm install && npm run build
cp .env.example .env          # then set DB_* and, for local dev: APP_ENV=local APP_DEBUG=true QUEUE_CONNECTION=sync MAIL_MAILER=log
php artisan key:generate
php artisan migrate --seed    # demo data (see credentials below)
php artisan serve             # http://localhost:8000
```

Optional in separate terminals: `php artisan schedule:work` (claim expiry, auto-approval, deadlines) and `php artisan queue:work` (if `QUEUE_CONNECTION=database`).

### Test credentials (local demo seed only)

| Role | Email | Password |
|---|---|---|
| Admin | `SEED_ADMIN_EMAIL` (default `admin@idletime.test`) | `SEED_ADMIN_PASSWORD` — if empty, a random one is generated and printed by the seeder |
| Developer | `dev1@idletime.test` … `dev10@idletime.test` | `SEED_DEMO_PASSWORD` (default `password`) |
| Requester | `acme@idletime.test`, `globex@idletime.test`, `initech@idletime.test` | `SEED_DEMO_PASSWORD` (default `password`) |

`dev10` ("Demo Dev Speedy") deliberately submits too fast so the fraud dashboard has a signal to review. Requesters start with $600 of demo funds. **Never run the demo seeder in production** — create the real admin with `php artisan platform:create-admin`.

### Tests & checks

```bash
php artisan test                    # 105 tests (SQLite in-memory by default)
DB_CONNECTION=mysql DB_DATABASE=idle_time_test php artisan test   # same suite against MySQL
vendor/bin/pint --test              # code style
php artisan wallet:reconcile        # verify every balance equals its ledger
```

---

## Documentation

- [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) — domain model, money handling, task lifecycle, design decisions
- [docs/API.md](docs/API.md) — REST API for desktop apps / IDE extensions
- [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) — Nginx/Apache, PHP-FPM, MySQL, cron, queue worker, admin setup

---

## Implemented features

**Public site** — Home, How it works, For developers, For businesses, Pricing (live commission maths), FAQ (with FAQPage JSON-LD); SEO titles/descriptions, Open Graph/Twitter tags, canonical URLs, `sitemap.xml`, environment-aware `robots.txt`; responsive dark UI.

**Accounts** — Registration as Developer or Task Requester, login/logout, password reset, email verification (enforcement toggle in settings), profile + developer profile (GitHub/LinkedIn/portfolio, skills, languages, experience), suspended-account lockout, last-login/IP tracking.

**Developers** — Dashboard (available/pending/today/lifetime earnings, completed count, approval rate, level, 14-day earnings chart, "Tasks Available Now", recent activity, in-progress banner with live timer); task discovery with filters (category, time, reward, difficulty, skill, type, search) and sorting (best reward/minute, highest reward, shortest, newest); task detail; claim → workspace with server-authoritative countdown, draft autosave, attachments for QA/bug tasks; release; submissions history with status, rejection reason and **appeals**; task rating; problem reports; wallet with ledger (available/pending), withdrawals (bank transfer / PayPal / other).

**Requesters** — Dashboard (funds, escrow, spending, active/completed tasks, pending reviews, completion rate, approval rate, avg. completion time, cost per approved task); task creation with live server-mirrored pricing (reward + fee = cost), attachments, deadline, skills, difficulty, answer format; start from admin templates; **batches** (one definition × N items, plain lines or JSON lines); edit while pending/rejected; pause/resume/cancel; add slots; per-task analytics; review queue with approve / reject (reason required) / request revision; billing with deposit requests and ledger.

**Admin** — Overview dashboard (developers, active developers, requesters, active tasks, completed today, rewards paid, platform revenue, pending queues) with charts (tasks completed over time, revenue vs developer earnings, tasks by category, review outcomes); task moderation with automated "potential issues" checklist, approve/reject (reason required)/suspend/reinstate/cancel, batch approval; submission review and **status override** (approve a rejection, or reverse an approval with clawback); users (filter, suspend/reactivate, wallet adjustments, fraud flags, shared-IP accounts, activity); withdrawals (pending → processing → paid / rejected+refund, audited view of encrypted payout details); deposits confirmation; disputes; reports; fraud signals (confirm/dismiss); categories; task templates; platform settings; activity log.

**Task engine** — Pluggable task types: Text Response, Multiple Choice, Code Review, Website QA, AI Evaluation (correctness/relevance/quality/safety), Documentation Verification, Bug Reproduction. Each type owns its payload validation, answer validation, and four Blade partials.

**Money** — Integer-cent `Money` value object (no floats), `DECIMAL` columns, escrow model, immutable append-only ledger with `balance_after`, every balance change inside a DB transaction with `SELECT … FOR UPDATE`, nightly reconciliation command.

**Trust & safety** — Heuristic fraud flags (fast completion, duplicate answers on a task, repeated identical answers, similar/aliased emails, many accounts per IP, excessive rejection rate) → fraud score for admin review, never auto-bans. Rate limits on login, registration, claims, submissions, drafts, withdrawals, reports and the API.

**Notifications & email** — Database notifications for developers (claimed, expiring, expired, approved, rejected, revision, withdrawal), requesters (task approved/rejected, new submission, task completed, moderation rejection, deposit) and admins (new task/batch, withdrawal, deposit, fraud flag, dispute, report); queued Markdown emails (welcome, verification, task approved/rejected, submission approved/rejected, withdrawal submitted/processed).

**API** — Sanctum token API with role abilities, API Resources, idle-session endpoints for future desktop/IDE integrations.

---

## Future improvements

- Payment provider drivers (Stripe/PayPal Payouts/Wise) behind the existing `PayoutGateway` / `FundingGateway` contracts, plus webhooks.
- KYC / tax forms before large withdrawals; per-country payout rules.
- Qualification tests and per-task minimum developer level or skill verification.
- Gold-standard ("honeypot") questions and inter-rater agreement for AI-evaluation batches; consensus review for multi-worker tasks.
- Desktop app / VS Code extension / Claude Code hook consuming `/api/idle-sessions` (the API is ready).
- Real-time updates (Laravel Reverb) for new tasks and review results.
- Requester teams, invoices and CSV export of results.
- Localization and multi-currency (the `Money` object and `currency` columns are ready for it).
- Larastan/PHPStan in CI (could not be installed in the build sandbox).
