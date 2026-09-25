<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActivityLogger
{
    public function log(string $action, ?Model $subject = null, array $properties = [], ?User $user = null): ActivityLog
    {
        $request = app()->runningInConsole() && ! app()->runningUnitTests() ? null : request();

        return ActivityLog::query()->create([
            'user_id' => $user?->id ?? auth()->id(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'properties' => $properties ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? Str::limit((string) $request->userAgent(), 250, '') : null,
            'created_at' => now(),
        ]);
    }
}
