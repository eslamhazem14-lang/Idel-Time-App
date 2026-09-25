<?php

namespace App\Models;

use App\Enums\FraudFlagStatus;
use App\Enums\FraudFlagType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FraudFlag extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => FraudFlagType::class,
            'status' => FraudFlagStatus::class,
            'details' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
