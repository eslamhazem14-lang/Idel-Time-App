<?php

namespace App\Policies;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /** Developers see live tasks, plus any task they have claimed; requesters see their own. */
    public function view(User $user, Task $task): bool
    {
        if ($user->isRequester()) {
            return $task->requester_id === $user->id;
        }

        return $task->status === TaskStatus::Active
            || $task->claims()->where('developer_id', $user->id)->exists();
    }

    public function claim(User $user, Task $task): bool
    {
        return $user->isDeveloper() && $task->requester_id !== $user->id;
    }

    public function manage(User $user, Task $task): bool
    {
        return $user->isRequester() && $task->requester_id === $user->id;
    }

    public function update(User $user, Task $task): bool
    {
        return $this->manage($user, $task)
            && $task->batch_id === null
            && in_array($task->status, [TaskStatus::PendingApproval, TaskStatus::Rejected], true);
    }

    /** Only developers who submitted work on a task may rate it. */
    public function review(User $user, Task $task): bool
    {
        return $user->isDeveloper() && $task->submissions()->where('developer_id', $user->id)->exists();
    }
}
