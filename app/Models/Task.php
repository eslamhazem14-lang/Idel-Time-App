<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\ClaimStatus;
use App\Enums\Difficulty;
use App\Enums\TaskStatus;
use App\Support\Money;
use App\TaskTypes\TaskTypeHandler;
use App\TaskTypes\TaskTypeRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property Money $reward
 * @property Money $platform_fee
 * @property Money $total_budget
 * @property Money $escrow_balance
 */
class Task extends Model
{
    use HasFactory;

    /**
     * Financial columns and counters are deliberately NOT fillable:
     * they are only ever computed server-side by TaskService.
     */
    protected $fillable = [
        'category_id', 'type', 'title', 'description', 'instructions', 'payload', 'answer_format',
        'estimated_minutes', 'difficulty', 'required_skills', 'deadline', 'template_id',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'required_skills' => 'array',
            'reward' => MoneyCast::class,
            'platform_fee' => MoneyCast::class,
            'total_budget' => MoneyCast::class,
            'escrow_balance' => MoneyCast::class,
            'commission_percent' => 'decimal:2',
            'status' => TaskStatus::class,
            'difficulty' => Difficulty::class,
            'deadline' => 'datetime',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
            'estimated_minutes' => 'integer',
            'available_slots' => 'integer',
            'reserved_slots' => 'integer',
            'completed_slots' => 'integer',
        ];
    }

    /** Tasks developers may claim right now. */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('tasks.status', TaskStatus::Active->value)
            ->whereRaw('tasks.available_slots > tasks.reserved_slots + tasks.completed_slots')
            ->where(fn ($q) => $q->whereNull('tasks.deadline')->orWhere('tasks.deadline', '>', now()));
    }

    /** Hide tasks the developer has already claimed (in any state). */
    public function scopeNotClaimedBy(Builder $query, User $developer): Builder
    {
        return $query->whereNotExists(fn ($q) => $q->selectRaw('1')->from('task_claims')
            ->whereColumn('task_claims.task_id', 'tasks.id')
            ->where('task_claims.developer_id', $developer->id));
    }

    public function remainingSlots(): int
    {
        return max(0, $this->available_slots - $this->reserved_slots - $this->completed_slots);
    }

    public function unitCost(): Money
    {
        return $this->reward->add($this->platform_fee);
    }

    public function rewardPerMinute(): string
    {
        return $this->reward->perMinute($this->estimated_minutes);
    }

    public function isClaimable(): bool
    {
        return $this->status === TaskStatus::Active
            && $this->remainingSlots() > 0
            && ($this->deadline === null || $this->deadline->isFuture());
    }

    public function handler(): TaskTypeHandler
    {
        return app(TaskTypeRegistry::class)->get($this->type);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TaskCategory::class, 'category_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(TaskBatch::class, 'batch_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(TaskClaim::class);
    }

    public function activeClaims(): HasMany
    {
        return $this->claims()->where('status', ClaimStatus::Active->value);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(TaskSubmission::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(TaskReview::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
