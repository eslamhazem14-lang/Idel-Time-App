<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\Difficulty;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskTemplate extends Model
{
    protected $fillable = [
        'category_id', 'name', 'type', 'title', 'description', 'instructions', 'estimated_minutes',
        'suggested_reward', 'difficulty', 'required_skills', 'answer_format', 'payload', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'suggested_reward' => MoneyCast::class,
            'difficulty' => Difficulty::class,
            'required_skills' => 'array',
            'payload' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TaskCategory::class, 'category_id');
    }
}
