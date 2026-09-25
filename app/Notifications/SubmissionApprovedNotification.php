<?php

namespace App\Notifications;

use App\Models\TaskSubmission;
use Illuminate\Notifications\Messages\MailMessage;

class SubmissionApprovedNotification extends PlatformNotification
{
    protected bool $sendsMail = true;

    public function __construct(public readonly TaskSubmission $submission) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject("Approved: +{$this->submission->reward->format()}")
            ->markdown('mail.submission-approved', ['submission' => $this->submission, 'user' => $notifiable]);
    }

    protected function title(): string
    {
        return 'Submission approved';
    }

    protected function body(): string
    {
        return "+{$this->submission->reward->format()} for “{$this->submission->task->title}”".($this->submission->auto_approved ? ' (auto-approved)' : '');
    }

    protected function url(): ?string
    {
        return route('developer.wallet');
    }

    protected function icon(): string
    {
        return 'coin';
    }
}
