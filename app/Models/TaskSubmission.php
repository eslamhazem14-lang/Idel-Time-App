<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\SubmissionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TaskSubmission extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'answer' => 'array',
            'reward' => MoneyCast::class,
            'status' => SubmissionStatus::class,
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'auto_approved' => 'boolean',
            'is_flagged' => 'boolean',
            'time_spent_seconds' => 'integer',
        ];
    }

    public function isPending(): bool
    {
        return $this->status === SubmissionStatus::Pending;
    }

    public function timeSpentForHumans(): string
    {
        $s = $this->time_spent_seconds;

        return $s < 60 ? "{$s}s" : intdiv($s, 60).'m '.str_pad((string) ($s % 60), 2, '0', STR_PAD_LEFT).'s';
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(TaskClaim::class, 'task_claim_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function developer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'developer_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function dispute(): HasOne
    {
        return $this->hasOne(Dispute::class, 'submission_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
