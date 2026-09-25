<?php

namespace App\Services;

use App\Enums\DisputeStatus;
use App\Enums\SubmissionStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Dispute;
use App\Models\TaskSubmission;
use App\Models\User;
use App\Notifications\AdminAlertNotification;
use App\Notifications\DisputeResolvedNotification;
use App\Support\AdminNotifier;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class DisputeService
{
    public const APPEAL_WINDOW_DAYS = 14;

    public function __construct(
        private readonly SubmissionReviewService $reviews,
        private readonly ActivityLogger $logger,
    ) {}

    public function open(TaskSubmission $submission, User $developer, string $reason): Dispute
    {
        if ($submission->developer_id !== $developer->id) {
            throw BusinessRuleException::make('You can only appeal your own submissions.', 'forbidden', 403);
        }
        if ($submission->status !== SubmissionStatus::Rejected) {
            throw BusinessRuleException::make('Only rejected submissions can be appealed.');
        }
        if ($submission->reviewed_at && $submission->reviewed_at->lt(now()->subDays(self::APPEAL_WINDOW_DAYS))) {
            throw BusinessRuleException::make('The appeal window for this submission has closed.');
        }

        try {
            $dispute = Dispute::query()->create([
                'submission_id' => $submission->id,
                'developer_id' => $developer->id,
                'reason' => $reason,
                'status' => DisputeStatus::Open,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw BusinessRuleException::make('This submission has already been appealed.');
        }

        $this->logger->log('dispute.opened', $dispute, [], $developer);
        AdminNotifier::send(new AdminAlertNotification(
            'New appeal', "{$developer->name} appealed a rejection on “{$submission->task->title}”",
            route('admin.disputes.show', $dispute), 'scale',
        ));

        return $dispute;
    }

    public function resolve(Dispute $dispute, User $admin, bool $uphold, string $note): Dispute
    {
        if ($dispute->status !== DisputeStatus::Open) {
            throw BusinessRuleException::make('This dispute is already resolved.');
        }

        DB::transaction(function () use ($dispute, $admin, $uphold, $note) {
            if ($uphold) {
                $this->reviews->overrideApprove($dispute->submission, $admin, 'Appeal upheld: '.$note);
            }
            $dispute->forceFill([
                'status' => $uphold ? DisputeStatus::Upheld : DisputeStatus::Denied,
                'resolution_note' => $note,
                'resolved_by' => $admin->id,
                'resolved_at' => now(),
            ])->save();
        });

        $this->logger->log('dispute.resolved', $dispute, ['upheld' => $uphold], $admin);
        $dispute->developer->notify(new DisputeResolvedNotification($dispute));

        return $dispute;
    }
}
