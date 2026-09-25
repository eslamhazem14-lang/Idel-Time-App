<?php

namespace App\Jobs;

use App\Services\TaskService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Close tasks whose deadline has passed and refund unused escrow. */
class CloseExpiredTasks implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 120;

    public function handle(TaskService $service): void
    {
        $service->closeExpired();
    }
}
