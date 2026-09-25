<?php

namespace App\Services;

use App\Enums\ClaimStatus;
use App\Enums\SubmissionStatus;
use App\Enums\TaskStatus;
use App\Enums\TransactionType;
use App\Enums\WalletBucket;
use App\Events\SubmissionReviewed;
use App\Exceptions\BusinessRuleException;
use App\Models\Task;
use App\Models\TaskClaim;
use App\Models\TaskSubmission;
use App\Models\User;
use App\Notifications\RequesterSubmissionRejectedNotification;
use App\Notifications\RevisionRequestedNotification;
use App\Notifications\SubmissionApprovedNotification;
use App\Notifications\SubmissionRejectedNotification;
use Illuminate\Support\Facades\DB;

class SubmissionReviewService
{
    public const MAX_REVISIONS = 2;

    public function __construct(
        private readonly TaskRewardService $rewards,
        private readonly TaskService $tasks,
        private readonly TaskClaimService $claims,
        private readonly WalletService $wallets,
        private readonly ActivityLogger $logger,
    ) {}

    public function approve(TaskSubmission $submission, ?User $reviewer, bool $auto = false): TaskSubmission
    {
        DB::transaction(function () use ($submission, $reviewer, $auto) {
            [$locked, $task] = $this->lockPending($submission);

            $locked->forceFill([
                'status' => SubmissionStatus::Approved,
                'reviewer_id' => $reviewer?->id,
                'reviewed_at' => now(),
                'auto_approved' => $auto,
                'rejection_reason' => null,
            ])->save();
            $locked->claim->forceFill(['status' => ClaimStatus::Approved])->save();

            $this->rewards->payApproved($locked, $task, $reviewer);
            $this->tasks->completeIfFull($task);
            $this->updateDeveloperStats($locked->developer);
        });

        $submission->refresh();
        $this->logger->log($auto ? 'submission.auto_approved' : 'submission.approved', $submission, [], $reviewer);
        $submission->developer->notify(new SubmissionApprovedNotification($submission));
        event(new SubmissionReviewed($submission));

        return $submission;
    }

    public function reject(TaskSubmission $submission, User $reviewer, string $reason): TaskSubmission
    {
        DB::transaction(function () use ($submission, $reviewer, $reason) {
            [$locked, $task] = $this->lockPending($submission);

            $locked->forceFill([
                'status' => SubmissionStatus::Rejected,
                'reviewer_id' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ])->save();
            $locked->claim->forceFill(['status' => ClaimStatus::Rejected])->save();

            $this->rewards->reversePending($locked, "{$task->title} — rejected", $reviewer);
            // The slot re-opens for another developer (or its escrow is refunded if the task closed).
            $this->tasks->releaseSlot($task);
            $this->updateDeveloperStats($locked->developer);
        });

        $submission->refresh();
        $this->logger->log('submission.rejected', $submission, ['reason' => $reason], $reviewer);
        $submission->developer->notify(new SubmissionRejectedNotification($submission));
        if ($reviewer->isAdmin() && $submission->task->requester_id !== $reviewer->id) {
            $submission->task->requester->notify(new RequesterSubmissionRejectedNotification($submission));
        }
        event(new SubmissionReviewed($submission));

        return $submission;
    }

    public function requestRevision(TaskSubmission $submission, User $reviewer, string $note): TaskSubmission
    {
        DB::transaction(function () use ($submission, $reviewer, $note) {
            [$locked, $task] = $this->lockPending($submission);

            $revisions = TaskSubmission::query()->where('task_claim_id', $locked->task_claim_id)
                ->where('status', SubmissionStatus::RevisionRequested->value)->count();
            if ($revisions >= self::MAX_REVISIONS) {
                throw BusinessRuleException::make('The revision limit for this submission was reached. Approve or reject it.');
            }

            $locked->forceFill([
                'status' => SubmissionStatus::RevisionRequested,
                'reviewer_id' => $reviewer->id,
                'reviewed_at' => now(),
                'revision_note' => $note,
            ])->save();

            // Re-open the claim with a fresh timer; the slot stays reserved.
            $now = now();
            $locked->claim->forceFill([
                'status' => ClaimStatus::Active,
                'expires_at' => $now->copy()->addMinutes($this->claims->claimDurationMinutes($task)),
                'expiry_warned_at' => null,
                'finished_at' => null,
                'draft' => $locked->answer,
            ])->save();

            $this->rewards->reversePending($locked, "{$task->title} — revision requested", $reviewer);
        });

        $submission->refresh();
        $this->logger->log('submission.revision_requested', $submission, ['note' => $note], $reviewer);
        $submission->developer->notify(new RevisionRequestedNotification($submission));

        return $submission;
    }

    /**
     * Admin override: approve a previously rejected submission (e.g. an upheld appeal).
     * Re-reserves a slot; if the task has none left, one extra slot is funded from the
     * requester's available balance.
     */
    public function overrideApprove(TaskSubmission $submission, User $admin, string $note): TaskSubmission
    {
        DB::transaction(function () use ($submission, $admin, $note) {
            $locked = TaskSubmission::query()->lockForUpdate()->findOrFail($submission->id);
            if ($locked->status !== SubmissionStatus::Rejected) {
                throw BusinessRuleException::make('Only rejected submissions can be overturned.');
            }
            $task = Task::query()->lockForUpdate()->findOrFail($locked->task_id);

            if ($task->remainingSlots() < 1 || $task->status->isClosed()) {
                $unit = $task->unitCost();
                $wallet = $this->wallets->walletFor($task->requester);
                if ($wallet->balance->lessThan($unit)) {
                    throw BusinessRuleException::make("The requester's available balance cannot fund an extra slot ({$unit->format()}). Adjust their wallet first.");
                }
                $this->wallets->moveBetweenBuckets($wallet, WalletBucket::Available, $unit, TransactionType::TaskEscrow,
                    "Extra slot funded for overturned submission #{$locked->id}", $task, $admin);
                $task->escrow_balance = $task->escrow_balance->add($unit);
                $task->available_slots++;
                $task->total_budget = $task->total_budget->add($unit);
            }
            $task->reserved_slots++;
            $task->save();

            $locked->forceFill(['status' => SubmissionStatus::Pending, 'rejection_reason' => null])->save();
            TaskClaim::query()->whereKey($locked->task_claim_id)->update(['status' => ClaimStatus::Submitted->value]);
            $this->rewards->restorePending($locked, "{$task->title} — re-opened by admin", $admin);

            $this->logger->log('submission.overridden', $locked, ['to' => 'approved', 'note' => $note], $admin);
        });

        return $this->approve($submission->refresh(), $admin);
    }

    /** Admin override: reverse a paid approval. */
    public function overrideReject(TaskSubmission $submission, User $admin, string $reason): TaskSubmission
    {
        DB::transaction(function () use ($submission, $admin, $reason) {
            $locked = TaskSubmission::query()->lockForUpdate()->findOrFail($submission->id);
            if ($locked->status !== SubmissionStatus::Approved) {
                throw BusinessRuleException::make('Only approved submissions can be reversed.');
            }
            $task = Task::query()->lockForUpdate()->findOrFail($locked->task_id);

            $this->rewards->clawBack($locked, $task, $admin, $reason);
            $locked->forceFill([
                'status' => SubmissionStatus::Rejected,
                'reviewer_id' => $admin->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ])->save();
            TaskClaim::query()->whereKey($locked->task_claim_id)->update(['status' => ClaimStatus::Rejected->value]);
            if ($task->status === TaskStatus::Completed && $task->completed_slots < $task->available_slots) {
                $task->status = TaskStatus::Active;
                $task->save();
            }
            $this->updateDeveloperStats($locked->developer);
            $this->logger->log('submission.overridden', $locked, ['to' => 'rejected', 'reason' => $reason], $admin);
        });

        $submission->refresh();
        $submission->developer->notify(new SubmissionRejectedNotification($submission));

        return $submission;
    }

    /** Approve submissions the requester has not reviewed in time (protects developers). */
    public function autoApproveStale(): int
    {
        $days = (int) settings('auto_approve_days', 3);
        if ($days < 1) {
            return 0;
        }
        $count = 0;
        TaskSubmission::query()
            ->where('status', SubmissionStatus::Pending->value)
            ->where('submitted_at', '<', now()->subDays($days))
            ->where('is_flagged', false)
            ->each(function (TaskSubmission $submission) use (&$count) {
                try {
                    $this->approve($submission, null, auto: true);
                    $count++;
                } catch (BusinessRuleException) {
                    // state changed concurrently; skip
                }
            });

        return $count;
    }

    public function updateDeveloperStats(User $developer): void
    {
        $counts = TaskSubmission::query()->where('developer_id', $developer->id)
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');
        $approved = (int) ($counts[SubmissionStatus::Approved->value] ?? 0);
        $rejected = (int) ($counts[SubmissionStatus::Rejected->value] ?? 0);
        $reviewed = $approved + $rejected;

        $developer->developerProfile()->updateOrCreate([], [])->forceFill([
            'completed_tasks' => $approved,
            'rejected_tasks' => $rejected,
            'approval_rate' => $reviewed ? round($approved * 100 / $reviewed, 2) : 0,
            'rating' => $reviewed ? round(1 + 4 * $approved / $reviewed, 2) : 0,
        ])->save();

        $level = 1;
        foreach (config('platform.developer_levels') as $lvl => $required) {
            if ($approved >= $required) {
                $level = $lvl;
            }
        }
        $developer->forceFill(['developer_level' => $level])->save();
    }

    /** @return array{0: TaskSubmission, 1: Task} */
    private function lockPending(TaskSubmission $submission): array
    {
        $locked = TaskSubmission::query()->lockForUpdate()->findOrFail($submission->id);
        if ($locked->status !== SubmissionStatus::Pending) {
            throw BusinessRuleException::make('This submission has already been reviewed.', 'already_reviewed', 409);
        }
        $task = Task::query()->lockForUpdate()->findOrFail($locked->task_id);

        return [$locked, $task];
    }
}
