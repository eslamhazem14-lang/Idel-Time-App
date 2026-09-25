<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\Task;
use App\Models\TaskSubmission;
use App\Models\User;

class AttachmentPolicy
{
    public function view(User $user, Attachment $attachment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $parent = $attachment->attachable;

        return match (true) {
            $parent instanceof Task => $user->can('view', $parent),
            $parent instanceof TaskSubmission => $user->can('view', $parent),
            default => false,
        };
    }
}
