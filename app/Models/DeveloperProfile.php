<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeveloperProfile extends Model
{
    protected $fillable = [
        'github_url', 'portfolio_url', 'linkedin_url', 'skills', 'languages', 'experience_years',
    ];

    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'languages' => 'array',
            'experience_years' => 'integer',
            'rating' => 'decimal:2',
            'approval_rate' => 'decimal:2',
            'completed_tasks' => 'integer',
            'rejected_tasks' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
