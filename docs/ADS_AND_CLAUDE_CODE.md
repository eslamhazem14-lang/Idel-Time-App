# Watch & earn + Claude Code integration

## The flow

1. **Claude starts working.** The Claude Code hook (`UserPromptSubmit`) calls
   `POST /api/idle-sessions` and opens `/watch` in the developer's browser
   (at most once per `IDLETIME_OPEN_COOLDOWN` minutes).
2. **The developer watches rewarded video ads** on `/watch`. The server records
   each view (`ad_views`) and only counts it if it lasted at least
   `ad_min_watch_seconds`, respects the cooldown and the daily cap, and was
   reported as "reward granted" by the ad network.
3. **Claude finishes.** The `Stop` hook ends the idle session; the open page
   shows "Claude finished" and can send a desktop notification.
4. **The ad network pays you** (Google: monthly, around the 21st, for the
   previous month, once you pass the payment threshold).
5. **You record that payment** in Admin → Ad revenue (period + amount
   received). `ad_revenue_share_percent` of it (default 50%) is split between
   developers in proportion to the counted views in that period, credited to
   their *available* balance and becomes withdrawable. Leftover cents from
   rounding stay with the platform. Views can only be paid once.

Until step 5 a developer only sees an **estimate** (`ad_estimated_view_value`
per view); no money is credited before you actually receive it, so the
platform never pays out money it hasn't got.

Ledger entries: `ad_revenue` (+gross to the platform wallet) and `ad_reward`
(−share from the platform, +share to each developer).

## Connecting Google

The page supports **Google Ad Manager rewarded ads for the web**
(GPT `OutOfPageFormat.REWARDED`). The ad plays full-screen, and Google fires
`rewardedSlotGranted` when it was watched to the end; that is when the view is
counted.

1. Create a Google Ad Manager account (https://admanager.google.com). Plain
   AdSense does **not** offer rewarded video for websites.
2. In Ad Manager: Inventory → Ad units → New ad unit, and enable it for the
   rewarded format. Note the path, e.g. `/1234567/idletime_rewarded`.
3. Serve demand into it (AdSense / Ad Exchange backfill or your own line items).
4. Set in `.env`:

   ```
   AD_PROVIDER=google_rewarded
   GOOGLE_AD_UNIT_PATH=/1234567/idletime_rewarded
   ```

5. Add an `ads.txt` file to `public/` with the line Google gives you.

> ⚠️ **Read Google's policies before going live.** Google's publisher
> policies forbid paying users to view or click ads (it is treated as
> *incentivized / invalid traffic*), and rewarded ads are meant for rewards
> like in-app items or unlocking content. Sharing cash revenue with viewers
> may get the account suspended and its earnings withheld. Ask your Google
> account manager for written approval before using it this way, or swap the
> provider for an ad or offerwall network whose terms explicitly allow cash
> rewards (the provider is one config value plus one JS function,
> `playGoogle()` in `resources/js/app.js`).

## Demo mode

With `AD_PROVIDER=demo` (the default) the page plays a built-in placeholder so
you can test the whole flow without an ad account. Demo views are counted only
when `ADS_PAY_DEMO_VIEWS=true`, which defaults to off in production.

## Settings (Admin → Settings → Watch & earn)

| Setting | Default | Meaning |
|---|---|---|
| `ads_enabled` | on | Show the page and accept views |
| `ad_revenue_share_percent` | 50 | Developers' share of each payout |
| `ad_estimated_view_value` | $0.01 | Estimate shown to developers only |
| `ad_min_watch_seconds` | 15 | Minimum time between start and "granted" |
| `ad_daily_cap` | 20 | Counted ads per developer per day |
| `ad_cooldown_seconds` | 30 | Gap between starting two ads |

## Claude Code hook

Developers set it up from **Connect Claude Code** in the sidebar: create a token,
run the install command, and add the hooks to `~/.claude/settings.json`:

```json
{
  "hooks": {
    "UserPromptSubmit": [{ "hooks": [{ "type": "command", "command": "~/.idletime/idletime-hook.sh start" }] }],
    "Stop": [{ "hooks": [{ "type": "command", "command": "~/.idletime/idletime-hook.sh stop" }] }]
  }
}
```

The script (`public/integrations/claude-code/idletime-hook.sh`) needs only
bash and curl, forks to the background, prints nothing, and always exits 0, so
it never slows Claude down. It works on macOS, Linux, WSL and Git Bash.
