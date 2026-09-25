<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification as NotificationFacade;

final class AdminNotifier
{
    public static function send(Notification $notification): void
    {
        DB::afterCommit(function () use ($notification) {
            $admins = User::query()->where('role', UserRole::Admin->value)->get();
            if ($admins->isNotEmpty()) {
                NotificationFacade::send($admins, $notification);
            }
        });
    }
}
