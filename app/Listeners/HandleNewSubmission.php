<?php

namespace App\Listeners;

use App\Events\SubmissionCreated;
use App\Notifications\NewSubmissionNotification;
use App\Services\FraudDetectionService;

class HandleNewSubmission
{
    public function __construct(private readonly FraudDetectionService $fraud) {}

    public function handle(SubmissionCreated $event): void
    {
        $submission = $event->submission;
        $this->fraud->inspectSubmission($submission);
        $submission->task->requester->notify(new NewSubmissionNotification($submission));
    }
}
