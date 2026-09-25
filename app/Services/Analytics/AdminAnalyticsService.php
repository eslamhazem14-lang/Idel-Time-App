<?php

namespace App\Services\Analytics;

use App\Enums\DisputeStatus;
use App\Enums\FraudFlagStatus;
use App\Enums\SubmissionStatus;
use App\Enums\TaskStatus;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Enums\WalletBucket;
use App\Enums\WithdrawalStatus;
use App\Models\Deposit;
use App\Models\Dispute;
use App\Models\FraudFlag;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\TaskSubmission;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use App\Services\WalletService;
use App\Support\Money;

class AdminAnalyticsService
{
    public function __construct(private readonly WalletService $wallets) {}

    public function cards(): array
    {
        $rewardsPaid = Money::sum(WalletTransaction::query()
            ->where('type', TransactionType::TaskReward->value)
            ->where('bucket', WalletBucket::Available->value)
            ->where('amount', '>', 0), 'amount');

        return [
            'developers' => User::query()->where('role', UserRole::Developer->value)->count(),
            'active_developers' => User::query()->where('role', UserRole::Developer->value)
                ->whereHas('submissions', fn ($q) => $q->where('submitted_at', '>=', now()->subDays(30)))->count(),
            'requesters' => User::query()->where('role', UserRole::Requester->value)->count(),
            'active_tasks' => Task::query()->where('status', TaskStatus::Active->value)->count(),
            'completed_today' => TaskSubmission::query()->where('status', SubmissionStatus::Approved->value)
                ->where('reviewed_at', '>=', now()->startOfDay())->count(),
            'rewards_paid' => $rewardsPaid,
            'platform_revenue' => $this->wallets->platformWallet()->balance,
            'pending_withdrawals' => Withdrawal::query()->whereIn('status', [WithdrawalStatus::Pending->value, WithdrawalStatus::Processing->value])->count(),
            'pending_withdrawals_amount' => Money::sum(Withdrawal::query()->whereIn('status', [WithdrawalStatus::Pending->value, WithdrawalStatus::Processing->value]), 'amount'),
            'pending_tasks' => Task::query()->where('status', TaskStatus::PendingApproval->value)->count(),
            'pending_submissions' => TaskSubmission::query()->where('status', SubmissionStatus::Pending->value)->count(),
            'pending_deposits' => Deposit::query()->where('status', 'pending')->count(),
            'open_disputes' => Dispute::query()->where('status', DisputeStatus::Open->value)->count(),
            'open_flags' => FraudFlag::query()->where('status', FraudFlagStatus::Open->value)->count(),
        ];
    }

    public function charts(int $days = 30): array
    {
        $from = now()->subDays($days - 1)->startOfDay();
        $labels = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $labels[] = now()->subDays($i)->toDateString();
        }

        $approvedByDay = TaskSubmission::query()->where('status', SubmissionStatus::Approved->value)
            ->where('reviewed_at', '>=', $from)->get(['reviewed_at'])
            ->countBy(fn ($s) => $s->reviewed_at->toDateString());

        $sumByDay = function (TransactionType $type, ?int $walletId = null) use ($from) {
            return WalletTransaction::query()->where('type', $type->value)
                ->where('bucket', WalletBucket::Available->value)
                ->when($walletId, fn ($q) => $q->where('wallet_id', $walletId))
                ->where('created_at', '>=', $from)->get(['amount', 'created_at'])
                ->groupBy(fn ($t) => $t->created_at->toDateString())
                ->map(fn ($g) => $g->reduce(fn (Money $c, $t) => $c->add($t->amount), Money::zero())->toDecimal());
        };
        $revenue = $sumByDay(TransactionType::PlatformFee, $this->wallets->platformWallet()->id);
        $earnings = WalletTransaction::query()->where('type', TransactionType::TaskReward->value)
            ->where('bucket', WalletBucket::Available->value)->where('amount', '>', 0)
            ->where('created_at', '>=', $from)->get(['amount', 'created_at'])
            ->groupBy(fn ($t) => $t->created_at->toDateString())
            ->map(fn ($g) => $g->reduce(fn (Money $c, $t) => $c->add($t->amount), Money::zero())->toDecimal());

        $categories = TaskCategory::query()->withCount('tasks')->orderByDesc('tasks_count')->get();
        $reviewCounts = TaskSubmission::query()->where('reviewed_at', '>=', $from)
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        return [
            'labels' => $labels,
            'completed' => array_map(fn ($d) => (int) ($approvedByDay[$d] ?? 0), $labels),
            'revenue' => array_map(fn ($d) => (float) ($revenue[$d] ?? 0), $labels),
            'earnings' => array_map(fn ($d) => (float) ($earnings[$d] ?? 0), $labels),
            'categories' => ['labels' => $categories->pluck('name'), 'data' => $categories->pluck('tasks_count')],
            'review' => [
                'approved' => (int) ($reviewCounts[SubmissionStatus::Approved->value] ?? 0),
                'rejected' => (int) ($reviewCounts[SubmissionStatus::Rejected->value] ?? 0),
                'revision' => (int) ($reviewCounts[SubmissionStatus::RevisionRequested->value] ?? 0),
            ],
        ];
    }
}
