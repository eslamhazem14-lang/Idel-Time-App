<?php

namespace App\Jobs;

use App\Services\SubmissionReviewService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Approve submissions the requester did not review within the configured number of days. */
class AutoApproveStaleSubmissions implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 120;

    public function handle(SubmissionReviewService $service): void
    {
        $service->autoApproveStale();
    }
}
