<?php

namespace App\Models;

use App\Enums\ClaimStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class TaskClaim extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'claimed_at' => 'datetime',
            'expires_at' => 'datetime',
            'draft_saved_at' => 'datetime',
            'expiry_warned_at' => 'datetime',
            'finished_at' => 'datetime',
            'status' => ClaimStatus::class,
            'draft' => 'array',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === ClaimStatus::Active;
    }

    /** Hard server-side cut-off: expiry plus the configured grace period. */
    public function submissionDeadline(): Carbon
    {
        return $this->expires_at->copy()->addSeconds((int) settings('claim_grace_seconds'));
    }

    public function hasTimedOut(?Carbon $now = null): bool
    {
        return ($now ?? now())->greaterThan($this->submissionDeadline());
    }

    public function secondsRemaining(): int
    {
        return max(0, (int) now()->diffInSeconds($this->expires_at, false));
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function developer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'developer_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(TaskSubmission::class);
    }

    public function latestSubmission(): HasOne
    {
        return $this->hasOne(TaskSubmission::class)->latestOfMany();
    }
}
