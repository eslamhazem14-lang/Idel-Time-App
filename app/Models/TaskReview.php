<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskReview extends Model
{
    protected $fillable = ['rating', 'clarity', 'time_accurate', 'comment'];

    protected function casts(): array
    {
        return ['time_accurate' => 'boolean', 'rating' => 'integer', 'clarity' => 'integer'];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function developer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'developer_id');
    }
}
