<?php

namespace App\Listeners;

use App\Enums\SubmissionStatus;
use App\Events\SubmissionReviewed;
use App\Services\FraudDetectionService;

class CheckDeveloperRejectionRate
{
    public function __construct(private readonly FraudDetectionService $fraud) {}

    public function handle(SubmissionReviewed $event): void
    {
        if ($event->submission->status === SubmissionStatus::Rejected) {
            $this->fraud->inspectRejectionRate($event->submission->developer);
        }
    }
}
