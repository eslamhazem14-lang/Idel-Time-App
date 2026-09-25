<?php

namespace App\Notifications;

use App\Models\TaskSubmission;

class RevisionRequestedNotification extends PlatformNotification
{
    public function __construct(public readonly TaskSubmission $submission) {}

    protected function title(): string
    {
        return 'Revision requested';
    }

    protected function body(): string
    {
        return "“{$this->submission->task->title}”: {$this->submission->revision_note}";
    }

    protected function url(): ?string
    {
        return route('developer.work.show', $this->submission->task_claim_id);
    }

    protected function icon(): string
    {
        return 'edit';
    }
}
