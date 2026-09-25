<?php

namespace App\Notifications;

use App\Models\TaskClaim;

class ClaimCancelledNotification extends PlatformNotification
{
    public function __construct(public readonly TaskClaim $claim, public readonly string $reason) {}

    protected function title(): string
    {
        return 'Task no longer available';
    }

    protected function body(): string
    {
        return "“{$this->claim->task->title}”: {$this->reason}";
    }

    protected function url(): ?string
    {
        return route('developer.tasks.index');
    }

    protected function icon(): string
    {
        return 'alert';
    }
}
