<?php

namespace App\Jobs;

use App\Services\TaskClaimService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Warn developers whose claim is about to expire. */
class SendClaimExpiryWarnings implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 120;

    public function handle(TaskClaimService $service): void
    {
        $service->sendExpiryWarnings();
    }
}
