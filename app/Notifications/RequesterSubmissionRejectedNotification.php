<?php

namespace App\Notifications;

use App\Models\TaskSubmission;

class RequesterSubmissionRejectedNotification extends PlatformNotification
{
    public function __construct(public readonly TaskSubmission $submission) {}

    protected function title(): string
    {
        return 'Submission rejected by moderation';
    }

    protected function body(): string
    {
        return "A submission on “{$this->submission->task->title}” was rejected by an administrator. The slot has been re-opened.";
    }

    protected function url(): ?string
    {
        return route('requester.tasks.show', $this->submission->task_id);
    }

    protected function icon(): string
    {
        return 'x';
    }
}
