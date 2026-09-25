<?php

namespace App\Models;

use App\Enums\AdViewStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdView extends Model
{
    protected $fillable = ['uuid', 'idle_session_id', 'provider', 'status', 'started_at', 'completed_at', 'ip', 'user_agent'];

    protected function casts(): array
    {
        return [
            'status' => AdViewStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function idleSession(): BelongsTo
    {
        return $this->belongsTo(IdleSession::class);
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(AdPayout::class, 'ad_payout_id');
    }
}
