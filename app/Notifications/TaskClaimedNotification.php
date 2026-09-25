<?php

namespace App\Notifications;

use App\Models\TaskClaim;

class TaskClaimedNotification extends PlatformNotification
{
    public function __construct(public readonly TaskClaim $claim) {}

    protected function title(): string
    {
        return 'Task started';
    }

    protected function body(): string
    {
        return "You claimed “{$this->claim->task->title}”. Submit before {$this->claim->expires_at->format('H:i')} UTC.";
    }

    protected function url(): ?string
    {
        return route('developer.work.show', $this->claim);
    }

    protected function icon(): string
    {
        return 'clock';
    }
}
