<?php

namespace App\Services\Analytics;

use App\Enums\SubmissionStatus;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\TaskClaim;
use App\Models\TaskSubmission;
use App\Models\User;
use App\Services\WalletService;
use App\Support\Money;

class RequesterAnalyticsService
{
    public function __construct(private readonly WalletService $wallets) {}

    public function summary(User $requester): array
    {
        $wallet = $this->wallets->walletFor($requester);
        $tasks = Task::query()->where('requester_id', $requester->id);
        $statusCounts = (clone $tasks)->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        $submissions = TaskSubmission::query()->whereIn('task_id', (clone $tasks)->select('id'));
        $subCounts = (clone $submissions)->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        return [
            'available' => $wallet->balance,
            'escrow' => $wallet->pending_balance,
            'spent' => $wallet->lifetime_spending,
            'active_tasks' => (int) ($statusCounts[TaskStatus::Active->value] ?? 0),
            'pending_approval' => (int) ($statusCounts[TaskStatus::PendingApproval->value] ?? 0),
            'completed_tasks' => (int) ($statusCounts[TaskStatus::Completed->value] ?? 0),
            'pending_submissions' => (int) ($subCounts[SubmissionStatus::Pending->value] ?? 0),
        ] + $this->rates(
            (int) ($subCounts[SubmissionStatus::Approved->value] ?? 0),
            (int) ($subCounts[SubmissionStatus::Rejected->value] ?? 0),
            TaskClaim::query()->whereIn('task_id', (clone $tasks)->select('id')),
            $submissions,
            $wallet->lifetime_spending,
        );
    }

    public function forTask(Task $task): array
    {
        $subCounts = $task->submissions()->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');
        $approved = (int) ($subCounts[SubmissionStatus::Approved->value] ?? 0);

        return [
            'pending_submissions' => (int) ($subCounts[SubmissionStatus::Pending->value] ?? 0),
            'rating' => $task->reviews()->avg('rating'),
        ] + $this->rates(
            $approved,
            (int) ($subCounts[SubmissionStatus::Rejected->value] ?? 0),
            $task->claims(),
            $task->submissions(),
            $task->unitCost()->multiply($approved),
        );
    }

    private function rates(int $approved, int $rejected, $claimsQuery, $submissionsQuery, Money $spent): array
    {
        $claims = (clone $claimsQuery)->count();
        $submitted = (clone $submissionsQuery)->distinct('task_claim_id')->count('task_claim_id');
        $avgSeconds = (int) (clone $submissionsQuery)->avg('time_spent_seconds');
        $reviewed = $approved + $rejected;

        return [
            'completion_rate' => $claims ? round($submitted * 100 / $claims, 1) : null,
            'approval_rate' => $reviewed ? round($approved * 100 / $reviewed, 1) : null,
            'avg_completion_seconds' => $avgSeconds,
            'cost_per_approved' => $approved ? Money::fromCents(intdiv($spent->cents, $approved)) : null,
            'approved' => $approved,
            'rejected' => $rejected,
        ];
    }
}
