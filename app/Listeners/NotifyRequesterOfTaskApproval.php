<?php

namespace App\Listeners;

use App\Events\TaskApproved;
use App\Notifications\TaskApprovedNotification;

class NotifyRequesterOfTaskApproval
{
    public function handle(TaskApproved $event): void
    {
        $event->task->requester->notify(new TaskApprovedNotification($event->task));
    }
}
