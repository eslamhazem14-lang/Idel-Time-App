<?php

namespace App\Services;

use App\Enums\AdViewStatus;
use App\Enums\TransactionType;
use App\Enums\WalletBucket;
use App\Exceptions\BusinessRuleException;
use App\Models\AdPayout;
use App\Models\AdView;
use App\Models\IdleSession;
use App\Models\User;
use App\Notifications\AdEarningsPaidNotification;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Rewarded video ads with revenue sharing.
 *
 * 1. A developer watches an ad to the end → the view is "counted" (no money yet).
 *    The server enforces minimum watch time, cooldown and a daily cap.
 * 2. The ad network (Google) pays the platform, typically monthly.
 * 3. An admin records that payout for a period; the developers' share
 *    (ad_revenue_share_percent) is split in proportion to counted views
 *    and credited to their available balance, where it can be withdrawn.
 */
class AdRewardService
{
    public function __construct(private readonly WalletService $wallets, private readonly ActivityLogger $logger) {}

    public function provider(): string
    {
        return (string) config('platform.ads.provider', 'demo');
    }

    /** Whether views on this server count towards revenue share (demo ads only count outside production). */
    public function countsViews(): bool
    {
        return $this->provider() !== 'demo' || (bool) config('platform.ads.pay_demo_views');
    }

    public function countedToday(User $user): int
    {
        return $user->adViews()->where('status', AdViewStatus::Counted)->where('completed_at', '>=', now()->startOfDay())->count()
            + $user->adViews()->where('status', AdViewStatus::Paid)->where('completed_at', '>=', now()->startOfDay())->count();
    }

    public function awaitingPayout(User $user): int
    {
        return $user->adViews()->where('status', AdViewStatus::Counted)->count();
    }

    /** An estimate only — the real amount is known when the ad network pays. */
    public function estimate(int $views): Money
    {
        return Money::fromCents($views * settings()->money('ad_estimated_view_value')->cents);
    }

    public function paidOut(User $user): Money
    {
        return Money::of((string) (app(WalletService::class)->walletFor($user)->transactions()
            ->where('type', TransactionType::AdReward)->sum('amount') ?: '0'));
    }

    public function start(User $user, ?IdleSession $idle = null, ?string $ip = null, ?string $userAgent = null): AdView
    {
        if (! settings('ads_enabled')) {
            throw BusinessRuleException::make('Watch & earn is currently turned off.', 'ads_disabled');
        }
        if ($this->countedToday($user) >= (int) settings('ad_daily_cap')) {
            throw BusinessRuleException::make("You've reached today's limit of ads. Come back tomorrow, or pick up a task.", 'ad_daily_cap');
        }

        $last = $user->adViews()->latest('started_at')->value('started_at');
        if ($last && now()->diffInSeconds($last, true) < (int) settings('ad_cooldown_seconds')) {
            throw BusinessRuleException::make('Please wait a few seconds before starting another ad.', 'ad_cooldown', 429);
        }

        return $user->adViews()->create([
            'uuid' => (string) Str::uuid(),
            'idle_session_id' => $idle?->user_id === $user->id ? $idle->id : null,
            'provider' => $this->provider(),
            'status' => AdViewStatus::Started,
            'started_at' => now(),
            'ip' => $ip,
            'user_agent' => $userAgent ? Str::limit($userAgent, 250, '') : null,
        ]);
    }

    /** Called when the ad network reports the reward was granted (the ad was watched to the end). */
    public function complete(User $user, AdView $view): AdView
    {
        if ($view->user_id !== $user->id) {
            throw BusinessRuleException::make('This ad view belongs to another account.', 'forbidden', 403);
        }

        return DB::transaction(function () use ($user, $view) {
            /** @var AdView $locked */
            $locked = AdView::query()->whereKey($view->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== AdViewStatus::Started) {
                throw BusinessRuleException::make('This ad was already counted.', 'ad_already_completed');
            }
            if ($locked->started_at->lt(now()->subMinutes((int) config('platform.ads.view_ttl_minutes', 30)))) {
                throw BusinessRuleException::make('This ad session expired. Start a new one.', 'ad_expired');
            }
            $minimum = (int) settings('ad_min_watch_seconds');
            if ($locked->started_at->diffInSeconds(now(), true) < $minimum) {
                throw BusinessRuleException::make("Watch the full ad to earn (at least {$minimum} seconds).", 'ad_too_short');
            }
            if ($this->countedToday($user) >= (int) settings('ad_daily_cap')) {
                throw BusinessRuleException::make("You've reached today's limit of ads.", 'ad_daily_cap');
            }

            $locked->forceFill([
                'status' => $this->countsViews() ? AdViewStatus::Counted : AdViewStatus::Unpaid,
                'completed_at' => now(),
            ])->save();

            return $locked;
        });
    }

    /**
     * Preview how a payout for the period would be split, without changing anything.
     *
     * @return array{views:int, developers:int, pool:Money, shares:array<int,array{views:int, amount:Money}>}
     */
    public function preview(CarbonInterface $from, CarbonInterface $to, Money $gross, ?string $sharePercent = null): array
    {
        $pool = $gross->percentage($sharePercent ?? (string) settings('ad_revenue_share_percent'));
        $counts = AdView::query()->where('status', AdViewStatus::Counted)
            ->whereBetween('completed_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->selectRaw('user_id, COUNT(*) as views')->groupBy('user_id')->pluck('views', 'user_id')
            ->map(fn ($v) => (int) $v);

        $total = $counts->sum();
        $shares = [];
        foreach ($counts as $userId => $views) {
            // Round each share down so the sum never exceeds the pool; the leftover cents stay with the platform.
            $shares[$userId] = ['views' => $views, 'amount' => Money::fromCents(intdiv($pool->cents * $views, max(1, $total)))];
        }

        return ['views' => $total, 'developers' => count($shares), 'pool' => $pool, 'shares' => $shares];
    }

    /** Record money received from the ad network and pay developers their share. */
    public function distribute(CarbonInterface $from, CarbonInterface $to, Money $gross, User $admin, ?string $reference = null, ?string $note = null): AdPayout
    {
        if (! $gross->isPositive()) {
            throw BusinessRuleException::make('Enter the amount the ad network paid you.', 'invalid_amount');
        }
        if ($from->gt($to)) {
            throw BusinessRuleException::make('The period start must be before its end.', 'invalid_period');
        }

        [$payout, $shares] = DB::transaction(function () use ($from, $to, $gross, $admin, $reference, $note) {
            // Lock the counted views of the period so two admins cannot pay them twice.
            $viewIds = AdView::query()->where('status', AdViewStatus::Counted)
                ->whereBetween('completed_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
                ->lockForUpdate()->pluck('id');
            if ($viewIds->isEmpty()) {
                throw BusinessRuleException::make('There are no watched ads awaiting payout in this period.', 'no_views');
            }

            $split = $this->preview($from, $to, $gross);
            $distributed = Money::fromCents(array_sum(array_map(fn ($s) => $s['amount']->cents, $split['shares'])));

            $payout = AdPayout::query()->create([
                'period_start' => $from->toDateString(),
                'period_end' => $to->toDateString(),
                'gross_amount' => $gross,
                'share_percent' => (string) settings('ad_revenue_share_percent'),
                'developer_pool' => $split['pool'],
                'distributed_amount' => $distributed,
                'view_count' => $split['views'],
                'developer_count' => $split['developers'],
                'reference' => $reference,
                'note' => $note,
                'created_by' => $admin->id,
            ]);

            $platform = $this->wallets->platformWallet();
            $this->wallets->post($platform, TransactionType::AdRevenue, WalletBucket::Available, $gross,
                "Ad network payout {$from->toDateString()} – {$to->toDateString()}", $payout, actor: $admin);

            $period = $from->format('M j').' – '.$to->format('M j, Y');
            foreach ($split['shares'] as $userId => $share) {
                if (! $share['amount']->isPositive()) {
                    continue;
                }
                $user = User::query()->findOrFail($userId);
                $this->wallets->post($platform, TransactionType::AdReward, WalletBucket::Available, $share['amount']->negate(),
                    "Ad revenue share to user #{$userId}", $payout, actor: $admin);
                $this->wallets->post($this->wallets->walletFor($user), TransactionType::AdReward, WalletBucket::Available, $share['amount'],
                    "Ad revenue share: {$share['views']} ads watched ({$period})", $payout, meta: ['views' => $share['views']], actor: $admin, lifetime: 'lifetime_earnings');
            }

            AdView::query()->whereIn('id', $viewIds)->update(['status' => AdViewStatus::Paid, 'ad_payout_id' => $payout->id]);
            $this->logger->log('ads.payout_distributed', $payout, ['gross' => $gross->toDecimal(), 'distributed' => $distributed->toDecimal(), 'views' => $split['views']], $admin);

            return [$payout, $split['shares']];
        });

        foreach ($shares as $userId => $share) {
            if ($share['amount']->isPositive()) {
                User::query()->find($userId)?->notify(new AdEarningsPaidNotification($payout, $share['amount']->toDecimal(), $share['views']));
            }
        }

        return $payout;
    }
}
