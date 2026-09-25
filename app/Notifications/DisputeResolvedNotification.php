<?php

namespace App\Notifications;

use App\Enums\DisputeStatus;
use App\Models\Dispute;

class DisputeResolvedNotification extends PlatformNotification
{
    public function __construct(public readonly Dispute $dispute) {}

    protected function title(): string
    {
        return $this->dispute->status === DisputeStatus::Upheld ? 'Appeal upheld' : 'Appeal denied';
    }

    protected function body(): string
    {
        return $this->dispute->status === DisputeStatus::Upheld ? 'Your appeal was accepted and the reward credited.' : "Your appeal was reviewed and denied: {$this->dispute->resolution_note}";
    }

    protected function url(): ?string
    {
        return route('developer.submissions.show', $this->dispute->submission_id);
    }

    protected function icon(): string
    {
        return 'scale';
    }
}
