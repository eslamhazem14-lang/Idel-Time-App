<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdleSession extends Model
{
    protected $fillable = ['client', 'agent', 'expected_minutes', 'started_at', 'ended_at', 'meta'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'ended_at' => 'datetime', 'meta' => 'array'];
    }

    public function minutesLeft(): int
    {
        if ($this->ended_at) {
            return 0;
        }

        return max(0, $this->expected_minutes - (int) $this->started_at->diffInMinutes(now()));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
