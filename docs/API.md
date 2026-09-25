# REST API

Base URL: `https://your-domain/api` · JSON only (`Accept: application/json`) · Auth: Sanctum bearer tokens.

Designed for the website's future companions: macOS/Windows apps, a VS Code extension, and Claude Code / Cursor integrations that detect "the agent is busy for N minutes" and offer matching tasks.

## Conventions

* Money is always a **decimal string** with two places (`"0.50"`), never a float. Currency: `USD`.
* Timestamps are ISO-8601 in UTC. Claim responses include `server_time` so clients can correct clock skew; the server is authoritative for deadlines.
* Lists are paginated (`data`, `links`, `meta`).
* Errors:
  * `401` unauthenticated · `403` wrong role / forbidden · `404` not found · `429` rate limited
  * `422` validation: `{ "message": "...", "errors": { "field": ["..."] } }`
  * Business rule violations: `{ "message": "This task was just claimed by another developer.", "code": "task_full" }` with status `409`, `403` or `422`.
  * Codes: `task_unavailable`, `task_full`, `already_claimed`, `claim_limit`, `claim_expired`, `already_submitted`, `not_claimed`, `already_reviewed`, `insufficient_funds`, `unverified`, `suspended`, `maintenance`, `forbidden`, `business_rule`.
* Rate limits: login 5/min per email+IP, registration 10/h per IP, API 120/min per user, claims 10/min, submissions 10/min, drafts 30/min, withdrawals 5/h.

## Authentication

### `POST /auth/login`
```json
{ "email": "dev1@idletime.test", "password": "password", "device_name": "vscode-extension" }
```
`200` → `{ "token": "1|abc…", "token_type": "Bearer", "user": { "id": 2, "role": "developer", … } }`

Tokens expire after 90 days and carry role abilities (`tasks:read`, `tasks:work`, `wallet:read`, `wallet:withdraw` for developers; `tasks:manage`, `submissions:review` for requesters).

### `POST /auth/register`
```json
{ "name": "Ada", "email": "ada@example.com", "password": "secret-pass-1", "password_confirmation": "secret-pass-1",
  "role": "developer", "accept_terms": true, "device_name": "macos-app" }
```
`201` → same shape as login. A verification email is sent; claiming/withdrawing requires a verified email when enforcement is on (`403 code=unverified`).

### `GET /auth/me` · `POST /auth/logout`

## Public

| Method | Path | Description |
|---|---|---|
| GET | `/categories` | Active task categories |
| GET | `/config` | Commission, minimum withdrawal, max task minutes, grace period, task types, maintenance flag |

## Developer endpoints (role `developer`)

| Method | Path | Description |
|---|---|---|
| GET | `/developer/dashboard` | Balances, today/lifetime earnings, completed, approval rate, level, active claim, tasks available now, recent activity |
| GET | `/tasks` | Available tasks. Query: `category` (slug), `max_minutes`, `min_reward`, `difficulty` (`easy/medium/hard`), `type`, `skill`, `q`, `sort` (`best_rate` default, `highest_reward`, `shortest`, `newest`), `per_page` (≤50) |
| GET | `/tasks/recommended?minutes=8&limit=5` | Best reward-per-minute tasks that fit an idle window |
| GET | `/tasks/{id}` | Task detail incl. `instructions`, `content` (type payload) and attachments (15-minute signed download URLs) |
| POST | `/tasks/{id}/claim` | Lock a slot and start the timer → `201` claim |
| POST | `/tasks/{id}/submit` | Submit the answer for your claim (`multipart/form-data` if sending `attachments[]`) → `201` submission |
| GET | `/claims?status=active\|all\|…` | Your claims |
| GET | `/claims/{id}` | One claim (expires it server-side if overdue) |
| POST | `/claims/{id}/draft` | Save a draft `{ "answer": {…} }` |
| POST | `/claims/{id}/release` | Give the task back |
| GET | `/submissions?status=` | Your submissions |
| GET | `/submissions/{id}` | Detail incl. rejection reason and appeal |
| POST | `/submissions/{id}/appeal` | `{ "reason": "…(≥20 chars)" }` |
| GET | `/wallet` | `available_balance`, `pending_balance`, `lifetime_earnings`, `minimum_withdrawal` |
| GET | `/wallet/transactions?bucket=available\|pending` | Ledger |
| GET | `/withdrawals` | Your withdrawals |
| POST | `/withdrawals` | `{ "amount": "15.00", "method": "paypal", "account_details": "me@example.com" }` |
| GET | `/notifications?unread=1` · POST `/notifications/{id}/read` | In-app notifications |

### Answer formats by task type

`answer` is an object whose shape depends on `task.type`:

| Type | Answer |
|---|---|
| `text_response` | `{ "text": "…" }` |
| `multiple_choice` | `{ "choices": [1] }` — indexes into `content.options`; one element unless `content.multiple` |
| `code_review` | `{ "verdict": "approve\|request_changes\|comment", "issues": "…(required when request_changes)", "answers": ["…one per content.questions"] }` |
| `website_qa` | `{ "result": "pass\|partial\|fail", "environment": "Chrome 128 / macOS", "findings": "…" }` + optional `attachments[]` |
| `ai_evaluation` | `{ "correctness": 1-5, "relevance": 1-5, "quality": 1-5, "safety": 1-5 (only if content.safety_applicable), "comments": "…" }` |
| `documentation_verification` | `{ "verdicts": ["accurate\|inaccurate\|unverifiable", …one per content.claims], "notes": "…" }` |
| `bug_reproduction` | `{ "reproduced": "yes\|no\|partially", "environment": "…", "notes": "…" }` |

### Example: claim → submit

```bash
curl -X POST https://host/api/tasks/42/claim -H "Authorization: Bearer $T" -H "Accept: application/json"
# 201 {"data":{"id":7,"status":"active","expires_at":"2026-09-25T10:20:00+00:00","seconds_remaining":600,"server_time":"…","task":{…}}}

curl -X POST https://host/api/tasks/42/submit -H "Authorization: Bearer $T" -H "Accept: application/json" \
     -H "Content-Type: application/json" -d '{"answer":{"text":"It returns one row with the value 1."}}'
# 201 {"data":{"id":31,"status":"pending","reward":"0.50",…}}
```

## Idle sessions (desktop / IDE integration)

A client that knows the AI agent just started a long job reports it; the API answers with tasks that fit.

| Method | Path | Description |
|---|---|---|
| POST | `/idle-sessions` | `{ "client": "vscode", "agent": "claude-code", "expected_minutes": 8, "meta": {} }` → `201 { session: { id, minutes_left, headline: "Your AI is working. You have 8 minutes available." }, recommended_tasks: [...] }`. Closes any previous open session. |
| GET | `/idle-sessions/current` | Current session with recomputed `minutes_left` and fresh recommendations |
| POST | `/idle-sessions/{id}/end` | Agent finished — end the session |

## Requester endpoints (role `requester`, prefix `/requester`)

| Method | Path | Description |
|---|---|---|
| GET | `/requester/dashboard` | Funds, escrow, spending, task counts, completion/approval rates, avg. time, cost per approved task |
| GET | `/requester/pricing/quote?reward=0.50&slots=10` | Server-side price: `reward`, `platform_fee`, `unit_cost`, `total` |
| GET | `/requester/tasks` | Your tasks (includes budget/escrow/slot fields) |
| POST | `/requester/tasks` | Create a task. Fields: `category_id`, `type`, `title`, `description`, `instructions`, `answer_format?`, `estimated_minutes`, `reward`, `slots`, `difficulty`, `required_skills[]`, `deadline?`, `payload{}` (type-specific), `attachments[]?`. Budget is reserved immediately; `422 code=insufficient_funds` if not enough balance. |
| GET | `/requester/tasks/{id}/submissions` | Submissions for your task |
| POST | `/requester/submissions/{id}/review` | `{ "decision": "approve" }` or `{ "decision": "reject\|revision", "reason": "…(≥10 chars)" }` |

Task `payload` by type: `text_response {question}` · `multiple_choice {question, options[]|"one per line", multiple}` · `code_review {language?, code, questions[]?}` · `website_qa {url, test_steps, devices?}` · `ai_evaluation {prompt, response, safety_applicable}` · `documentation_verification {source_url? , excerpt?, claims[]}` · `bug_reproduction {environment?, steps, expected, actual?}`.
