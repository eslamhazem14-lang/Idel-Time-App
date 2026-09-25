<?php

namespace App\Services\Analytics;

use App\Enums\ClaimStatus;
use App\Enums\SubmissionStatus;
use App\Enums\TransactionType;
use App\Enums\WalletBucket;
use App\Models\TaskClaim;
use App\Models\TaskSubmission;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use App\Support\Money;

class DeveloperStatsService
{
    public function __construct(private readonly WalletService $wallets) {}

    public function summary(User $developer): array
    {
        $wallet = $this->wallets->walletFor($developer);
        $counts = TaskSubmission::query()->where('developer_id', $developer->id)
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');
        $approved = (int) ($counts[SubmissionStatus::Approved->value] ?? 0);
        $rejected = (int) ($counts[SubmissionStatus::Rejected->value] ?? 0);
        $reviewed = $approved + $rejected;

        $today = Money::sum(WalletTransaction::query()
            ->where('wallet_id', $wallet->id)
            ->where('type', TransactionType::TaskReward->value)
            ->where('bucket', WalletBucket::Available->value)
            ->where('amount', '>', 0)
            ->where('created_at', '>=', now()->startOfDay()), 'amount');

        return [
            'available' => $wallet->balance,
            'pending' => $wallet->pending_balance,
            'today' => $today,
            'lifetime' => $wallet->lifetime_earnings,
            'completed' => $approved,
            'pending_reviews' => (int) ($counts[SubmissionStatus::Pending->value] ?? 0),
            'approval_rate' => $reviewed ? round($approved * 100 / $reviewed, 1) : null,
            'level' => $developer->developer_level,
        ];
    }

    public function activeClaim(User $developer): ?TaskClaim
    {
        return TaskClaim::query()->with('task.category')
            ->where('developer_id', $developer->id)
            ->where('status', ClaimStatus::Active->value)
            ->latest('claimed_at')->first();
    }

    public function recentActivity(User $developer, int $limit = 8)
    {
        return TaskSubmission::query()->with('task.category')
            ->where('developer_id', $developer->id)
            ->latest('submitted_at')->limit($limit)->get();
    }

    /** Earnings per day for the last N days (for a sparkline). */
    public function earningsSeries(User $developer, int $days = 14): array
    {
        $wallet = $this->wallets->walletFor($developer);
        $rows = WalletTransaction::query()
            ->where('wallet_id', $wallet->id)
            ->where('type', TransactionType::TaskReward->value)
            ->where('bucket', WalletBucket::Available->value)
            ->where('created_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->get(['amount', 'created_at'])
            ->groupBy(fn ($t) => $t->created_at->toDateString());

        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $sum = collect($rows->get($date, []))->reduce(fn (Money $c, $t) => $c->add($t->amount), Money::zero());
            $series[] = ['date' => $date, 'amount' => $sum->toDecimal()];
        }

        return $series;
    }
}
