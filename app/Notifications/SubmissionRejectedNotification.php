<?php

namespace App\Notifications;

use App\Models\TaskSubmission;
use Illuminate\Notifications\Messages\MailMessage;

class SubmissionRejectedNotification extends PlatformNotification
{
    protected bool $sendsMail = true;

    public function __construct(public readonly TaskSubmission $submission) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject("Submission rejected: {$this->submission->task->title}")
            ->markdown('mail.submission-rejected', ['submission' => $this->submission, 'user' => $notifiable]);
    }

    protected function title(): string
    {
        return 'Submission rejected';
    }

    protected function body(): string
    {
        return "“{$this->submission->task->title}”: {$this->submission->rejection_reason}";
    }

    protected function url(): ?string
    {
        return route('developer.submissions.show', $this->submission);
    }

    protected function icon(): string
    {
        return 'x';
    }
}
