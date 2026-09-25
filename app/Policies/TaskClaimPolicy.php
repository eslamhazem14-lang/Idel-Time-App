<?php

namespace App\Policies;

use App\Models\TaskClaim;
use App\Models\User;

class TaskClaimPolicy
{
    public function work(User $user, TaskClaim $claim): bool
    {
        return $user->isDeveloper() && $claim->developer_id === $user->id;
    }
}
