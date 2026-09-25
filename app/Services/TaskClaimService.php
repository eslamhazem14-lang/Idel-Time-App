<?php

namespace App\Services;

use App\Enums\ClaimStatus;
use App\Enums\TaskStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Task;
use App\Models\TaskClaim;
use App\Models\User;
use App\Notifications\ClaimExpiredNotification;
use App\Notifications\ClaimExpiringNotification;
use App\Notifications\TaskClaimedNotification;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Claiming locks a task slot for one developer for a limited, server-defined
 * time window. Timers are authoritative on the server (claimed_at/expires_at);
 * the browser countdown is only a visual aid.
 */
class TaskClaimService
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly TaskService $tasks,
        private readonly ActivityLogger $logger,
    ) {}

    public function claimDurationMinutes(Task $task): int
    {
        $multiplier = (float) $this->settings->get('claim_duration_multiplier', 2);

        return max((int) $this->settings->get('claim_min_minutes', 5), (int) ceil($task->estimated_minutes * $multiplier));
    }

    public function claim(Task $task, User $developer, ?string $ip = null): TaskClaim
    {
        $this->assertCanWork($developer);

        // Free up any slots held by timed-out claims first so availability is accurate
        // even when the scheduler is not running.
        $this->expireOverdue($task->id);

        try {
            $claim = DB::transaction(function () use ($task, $developer, $ip) {
                $locked = Task::query()->lockForUpdate()->find($task->id);

                if (! $locked || $locked->status !== TaskStatus::Active) {
                    throw BusinessRuleException::make('This task is no longer available.', 'task_unavailable', 409);
                }
                if ($locked->deadline && $locked->deadline->isPast()) {
                    throw BusinessRuleException::make('This task has passed its deadline.', 'task_unavailable', 409);
                }
                if ($locked->requester_id === $developer->id) {
                    throw BusinessRuleException::make('You cannot work on your own task.', 'forbidden', 403);
                }
                if (TaskClaim::query()->where('task_id', $locked->id)->where('developer_id', $developer->id)->exists()) {
                    throw BusinessRuleException::make('You have already worked on this task.', 'already_claimed', 409);
                }

                $active = TaskClaim::query()->where('developer_id', $developer->id)
                    ->where('status', ClaimStatus::Active->value)->lockForUpdate()->count();
                if ($active >= (int) $this->settings->get('max_active_claims', 1)) {
                    throw BusinessRuleException::make('Finish or release your current task before starting another one.', 'claim_limit', 409);
                }

                if ($locked->remainingSlots() < 1) {
                    throw BusinessRuleException::make('This task was just claimed by another developer.', 'task_full', 409);
                }

                $now = now();
                $claim = TaskClaim::query()->create([
                    'task_id' => $locked->id,
                    'developer_id' => $developer->id,
                    'claimed_at' => $now,
                    'expires_at' => $now->copy()->addMinutes($this->claimDurationMinutes($locked)),
                    'status' => ClaimStatus::Active,
                    'ip_address' => $ip,
                ]);

                $locked->reserved_slots++;
                $locked->save();

                return $claim;
            });
        } catch (UniqueConstraintViolationException) {
            throw BusinessRuleException::make('You have already worked on this task.', 'already_claimed', 409);
        }

        $this->logger->log('claim.created', $claim, ['task_id' => $task->id], $developer);
        $developer->notify(new TaskClaimedNotification($claim));

        return $claim->setRelation('task', $task->refresh());
    }

    /** Developer gives the task back before submitting. */
    public function release(TaskClaim $claim): void
    {
        DB::transaction(function () use ($claim) {
            $locked = TaskClaim::query()->lockForUpdate()->findOrFail($claim->id);
            if ($locked->status !== ClaimStatus::Active) {
                throw BusinessRuleException::make('Only an in-progress task can be released.');
            }
            $locked->forceFill(['status' => ClaimStatus::Cancelled, 'finished_at' => now()])->save();
            $this->tasks->releaseSlot(Task::query()->lockForUpdate()->findOrFail($locked->task_id));
        });

        $this->logger->log('claim.released', $claim);
    }

    public function saveDraft(TaskClaim $claim, array $draft): void
    {
        if (! $claim->isActive() || $claim->hasTimedOut()) {
            throw BusinessRuleException::make('Your task expired.', 'claim_expired', 409);
        }
        if (strlen(json_encode($draft)) > 64000) {
            throw BusinessRuleException::make('Draft is too large.');
        }
        $claim->forceFill(['draft' => $draft, 'draft_saved_at' => now()])->save();
    }

    /**
     * Expire every active claim past its deadline + grace period.
     */
    public function expireOverdue(?int $taskId = null): int
    {
        $cutoff = now()->subSeconds((int) $this->settings->get('claim_grace_seconds', 60));
        $count = 0;

        TaskClaim::query()
            ->where('status', ClaimStatus::Active->value)
            ->where('expires_at', '<', $cutoff)
            ->when($taskId, fn ($q) => $q->where('task_id', $taskId))
            ->orderBy('id')
            ->each(function (TaskClaim $claim) use (&$count) {
                if ($this->expire($claim)) {
                    $count++;
                }
            });

        return $count;
    }

    /**
     * Expire a single timed-out claim according to the configured policy.
     * Returns true when the claim was changed.
     */
    public function expire(TaskClaim $claim): bool
    {
        $policy = $this->settings->get('expired_claim_policy', 'expire');

        if ($policy === 'submit_draft' && $this->submitDraftIfValid($claim)) {
            return true;
        }

        $expired = DB::transaction(function () use ($claim) {
            $locked = TaskClaim::query()->lockForUpdate()->find($claim->id);
            if (! $locked || $locked->status !== ClaimStatus::Active || ! $locked->hasTimedOut()) {
                return false;
            }
            $locked->forceFill(['status' => ClaimStatus::Expired, 'finished_at' => now()])->save();
            $this->tasks->releaseSlot(Task::query()->lockForUpdate()->findOrFail($locked->task_id));

            return true;
        });

        if ($expired) {
            $this->logger->log('claim.expired', $claim, [], $claim->developer);
            $claim->developer->notify(new ClaimExpiredNotification($claim));
        }

        return $expired;
    }

    public function sendExpiryWarnings(): int
    {
        $minutes = (int) $this->settings->get('expiry_warning_minutes', 2);
        $claims = TaskClaim::query()->with('task', 'developer')
            ->where('status', ClaimStatus::Active->value)
            ->whereNull('expiry_warned_at')
            ->whereBetween('expires_at', [now(), now()->addMinutes($minutes)])
            ->get();

        foreach ($claims as $claim) {
            $claim->forceFill(['expiry_warned_at' => now()])->save();
            $claim->developer->notify(new ClaimExpiringNotification($claim));
        }

        return $claims->count();
    }

    public function assertCanWork(User $developer): void
    {
        if (! $developer->isDeveloper()) {
            throw BusinessRuleException::make('Only developer accounts can work on tasks.', 'forbidden', 403);
        }
        if ($developer->isSuspended()) {
            throw BusinessRuleException::make('Your account is suspended.', 'suspended', 403);
        }
        if ($this->settings->get('require_email_verification') && ! $developer->hasVerifiedEmail()) {
            throw BusinessRuleException::make('Please verify your email address before starting tasks.', 'unverified', 403);
        }
    }

    private function submitDraftIfValid(TaskClaim $claim): bool
    {
        $claim->loadMissing('task');
        if (! $claim->isActive() || empty($claim->draft)) {
            return false;
        }

        $handler = $claim->task->handler();
        $input = $handler->normalizeAnswer($claim->task, $claim->draft);
        $validator = Validator::make($input, $handler->answerRules($claim->task));
        if ($validator->fails()) {
            return false;
        }

        app(TaskSubmissionService::class)->submit($claim, $handler->prepareAnswer($claim->task, $validator->validated()), [], null, fromExpiredDraft: true);

        return true;
    }
}
