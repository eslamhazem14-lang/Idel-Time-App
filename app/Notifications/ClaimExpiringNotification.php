<?php

namespace App\Notifications;

use App\Models\TaskClaim;

class ClaimExpiringNotification extends PlatformNotification
{
    public function __construct(public readonly TaskClaim $claim) {}

    protected function title(): string
    {
        return 'Your task is about to expire';
    }

    protected function body(): string
    {
        return "“{$this->claim->task->title}” expires in less than ".settings('expiry_warning_minutes').' minutes.';
    }

    protected function url(): ?string
    {
        return route('developer.work.show', $this->claim);
    }

    protected function icon(): string
    {
        return 'alert';
    }
}
