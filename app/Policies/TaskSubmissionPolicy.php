<?php

namespace App\Policies;

use App\Models\TaskSubmission;
use App\Models\User;

class TaskSubmissionPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, TaskSubmission $submission): bool
    {
        return $submission->developer_id === $user->id
            || ($user->isRequester() && $submission->task->requester_id === $user->id);
    }

    public function review(User $user, TaskSubmission $submission): bool
    {
        return $user->isRequester() && $submission->task->requester_id === $user->id;
    }

    public function appeal(User $user, TaskSubmission $submission): bool
    {
        return $user->isDeveloper() && $submission->developer_id === $user->id;
    }
}
