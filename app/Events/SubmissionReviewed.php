<?php

namespace App\Events;

use App\Models\TaskSubmission;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubmissionReviewed
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly TaskSubmission $submission) {}
}
