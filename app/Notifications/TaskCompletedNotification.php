<?php

namespace App\Notifications;

use App\Models\Task;

class TaskCompletedNotification extends PlatformNotification
{
    public function __construct(public readonly Task $task) {}

    protected function title(): string
    {
        return 'Task completed';
    }

    protected function body(): string
    {
        return "All {$this->task->available_slots} slot(s) of “{$this->task->title}” have approved submissions.";
    }

    protected function url(): ?string
    {
        return route('requester.tasks.show', $this->task);
    }

    protected function icon(): string
    {
        return 'check';
    }
}
