<?php

namespace App\Services;

use App\Enums\ClaimStatus;
use App\Enums\SubmissionStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\WalletBucket;
use App\Events\SubmissionCreated;
use App\Exceptions\BusinessRuleException;
use App\Models\Task;
use App\Models\TaskClaim;
use App\Models\TaskSubmission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class TaskSubmissionService
{
    public function __construct(
        private readonly WalletService $wallets,
        private readonly TaskService $tasks,
        private readonly AttachmentService $attachments,
        private readonly ActivityLogger $logger,
    ) {}

    /**
     * @param  array  $answer  already validated + prepared by the task type handler
     * @param  UploadedFile[]  $files
     */
    public function submit(TaskClaim $claim, array $answer, array $files = [], ?string $ip = null, bool $fromExpiredDraft = false): TaskSubmission
    {
        $result = DB::transaction(function () use ($claim, $answer, $files, $ip, $fromExpiredDraft) {
            $locked = TaskClaim::query()->lockForUpdate()->findOrFail($claim->id);

            if ($locked->status === ClaimStatus::Expired) {
                throw BusinessRuleException::make('Your task expired.', 'claim_expired', 409);
            }
            if ($locked->status !== ClaimStatus::Active) {
                throw BusinessRuleException::make('This task has already been submitted.', 'already_submitted', 409);
            }

            $task = Task::query()->lockForUpdate()->findOrFail($locked->task_id);

            // Server-authoritative timer: reject anything after expiry + grace.
            if (! $fromExpiredDraft && $locked->hasTimedOut()) {
                $locked->forceFill(['status' => ClaimStatus::Expired, 'finished_at' => now()])->save();
                $this->tasks->releaseSlot($task);

                return 'expired';
            }

            $handler = $task->handler();
            $fingerprint = $handler->fingerprint($answer);
            $submittedAt = $fromExpiredDraft ? $locked->expires_at : now();

            $submission = TaskSubmission::query()->create([
                'task_claim_id' => $locked->id,
                'developer_id' => $locked->developer_id,
                'task_id' => $task->id,
                'answer' => $answer,
                'answer_hash' => hash('sha256', $fingerprint !== null ? self::normalizeText($fingerprint) : json_encode($answer)),
                'submitted_at' => $submittedAt,
                'time_spent_seconds' => max(0, (int) $locked->claimed_at->diffInSeconds($submittedAt)),
                'status' => SubmissionStatus::Pending,
                'reward' => $task->reward,
                'ip_address' => $ip,
            ]);

            $locked->forceFill(['status' => ClaimStatus::Submitted, 'finished_at' => now(), 'draft' => null])->save();

            $this->wallets->post(
                $this->wallets->walletFor($locked->developer), TransactionType::TaskReward, WalletBucket::Pending, $task->reward,
                "{$task->title} — awaiting review", $submission, TransactionStatus::Pending,
            );

            foreach ($files as $file) {
                $this->attachments->store($file, $submission, $locked->developer);
            }

            return $submission;
        });

        if ($result === 'expired') {
            $this->logger->log('claim.expired', $claim);
            throw BusinessRuleException::make('Your task expired.', 'claim_expired', 409);
        }

        $this->logger->log('submission.created', $result, ['auto_from_draft' => $fromExpiredDraft], $claim->developer);
        event(new SubmissionCreated($result));

        return $result;
    }

    public static function normalizeText(string $text): string
    {
        return trim(preg_replace('/\s+/', ' ', mb_strtolower($text)));
    }
}
