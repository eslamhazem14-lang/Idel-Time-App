<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\BatchStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskBatch extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'reward' => MoneyCast::class,
            'platform_fee' => MoneyCast::class,
            'total_budget' => MoneyCast::class,
            'status' => BatchStatus::class,
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TaskCategory::class, 'category_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'batch_id');
    }
}
