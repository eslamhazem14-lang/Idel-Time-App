<?php

namespace App\Jobs;

use App\Services\TaskClaimService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Expire active claims past their deadline + grace period and release their slots. */
class ExpireStaleClaims implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 120;

    public function handle(TaskClaimService $service): void
    {
        $service->expireOverdue();
    }
}
