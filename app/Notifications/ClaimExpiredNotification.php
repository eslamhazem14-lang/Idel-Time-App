<?php

namespace App\Notifications;

use App\Models\TaskClaim;

class ClaimExpiredNotification extends PlatformNotification
{
    public function __construct(public readonly TaskClaim $claim) {}

    protected function title(): string
    {
        return 'Your task expired';
    }

    protected function body(): string
    {
        return "Time ran out on “{$this->claim->task->title}”. The slot was released to other developers.";
    }

    protected function url(): ?string
    {
        return route('developer.tasks.index');
    }

    protected function icon(): string
    {
        return 'clock';
    }
}
