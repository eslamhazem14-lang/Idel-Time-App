<?php

namespace App\Notifications;

use App\Models\TaskSubmission;

class NewSubmissionNotification extends PlatformNotification
{
    public function __construct(public readonly TaskSubmission $submission) {}

    protected function title(): string
    {
        return 'New submission to review';
    }

    protected function body(): string
    {
        return "A developer submitted work on “{$this->submission->task->title}”.";
    }

    protected function url(): ?string
    {
        return route('requester.submissions.show', $this->submission);
    }

    protected function icon(): string
    {
        return 'inbox';
    }
}
