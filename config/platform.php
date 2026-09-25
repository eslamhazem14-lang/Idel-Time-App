<?php

/*
|--------------------------------------------------------------------------
| Platform defaults
|--------------------------------------------------------------------------
|
| These are DEFAULT values. Every key under "settings" can be changed at
| runtime by an administrator (Admin → Settings); stored values live in the
| `settings` table and take precedence over the defaults below.
|
*/

return [
    'name' => env('APP_NAME', 'IdleTime'),
    'currency' => 'USD',
    'currency_symbol' => '$',

    'settings' => [
        // Business model
        'commission_percent' => env('PLATFORM_COMMISSION_PERCENT', '30.00'),
        'min_reward' => '0.10',
        'max_reward' => '25.00',

        // Task limits (minutes)
        'min_task_minutes' => 1,
        'max_task_minutes' => 15,

        // Claims: time allowed = estimated minutes × multiplier (never below claim_min_minutes)
        'claim_duration_multiplier' => '2',
        'claim_min_minutes' => 5,
        'claim_grace_seconds' => 60,
        // What happens to an expired claim: "expire" discards it, "submit_draft" auto-submits a saved draft
        'expired_claim_policy' => 'expire',
        'max_active_claims' => 1,
        'expiry_warning_minutes' => 2,

        // Review
        'auto_approve_days' => 3,

        // Wallet
        'min_withdrawal' => env('PLATFORM_MIN_WITHDRAWAL', '10.00'),
        'withdrawal_methods' => ['bank_transfer', 'paypal', 'other'],
        'min_deposit' => '10.00',

        // Accounts
        'require_email_verification' => (bool) env('PLATFORM_REQUIRE_EMAIL_VERIFICATION', true),
        'maintenance_mode' => false,
        'maintenance_message' => 'We are performing scheduled maintenance. Please check back shortly.',

        // Rewarded video ads ("Watch & earn" while the AI agent works)
        'ads_enabled' => (bool) env('ADS_ENABLED', true),
        // Share of the ad revenue Google pays you that goes to the developers who watched
        'ad_revenue_share_percent' => env('AD_REVENUE_SHARE_PERCENT', '50.00'),
        // Rough value of one watched ad to the developer, shown as an ESTIMATE only (never credited)
        'ad_estimated_view_value' => '0.01',
        'ad_min_watch_seconds' => 15,
        'ad_daily_cap' => 20,
        'ad_cooldown_seconds' => 30,
    ],

    /*
    | Ad provider. "demo" plays a built-in placeholder so the flow can be tested
    | without an ad account; "google_rewarded" uses Google Ad Manager rewarded
    | web ads (GPT). Demo views only pay out when ads.pay_demo_views is true,
    | which defaults to false in production so nobody earns from a fake ad.
    */
    'ads' => [
        'provider' => env('AD_PROVIDER', 'demo'),
        'pay_demo_views' => (bool) env('ADS_PAY_DEMO_VIEWS', env('APP_ENV', 'production') !== 'production'),
        'google' => [
            // e.g. /1234567/rewarded_web  (Ad Manager network code + ad unit)
            'ad_unit_path' => env('GOOGLE_AD_UNIT_PATH'),
        ],
        // A started view that is not completed within this window can no longer be rewarded.
        'view_ttl_minutes' => 30,
    ],

    'fraud' => [
        // Completing in less than this fraction of the estimated time is suspicious
        'fast_completion_ratio' => 0.15,
        'rejection_rate_threshold' => 0.5,
        'rejection_rate_min_reviews' => 5,
        'repeated_answer_threshold' => 3,
        'shared_ip_threshold' => 3,
        'review_score' => 40, // accounts at or above this score are flagged for admin review
    ],

    'uploads' => [
        'max_kb' => 5120,
        'max_files' => 5,
        'extensions' => ['png', 'jpg', 'jpeg', 'gif', 'webp', 'pdf', 'txt', 'md', 'csv', 'json', 'log', 'zip'],
        'mimetypes' => [
            'image/png', 'image/jpeg', 'image/gif', 'image/webp', 'application/pdf',
            'text/plain', 'text/markdown', 'text/csv', 'application/csv', 'application/json',
            'application/zip', 'application/x-zip-compressed',
        ],
        'disk' => env('ATTACHMENTS_DISK', 'local'),
    ],

    'developer_levels' => [
        // level => completed tasks required
        1 => 0, 2 => 10, 3 => 50, 4 => 150, 5 => 500,
    ],

    'batch' => [
        'max_items' => 2000,
    ],
];
