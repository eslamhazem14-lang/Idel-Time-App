<?php

namespace App\Services;

use App\DTOs\PriceQuote;
use App\Enums\BatchStatus;
use App\Enums\ClaimStatus;
use App\Enums\TaskStatus;
use App\Enums\TransactionType;
use App\Enums\WalletBucket;
use App\Events\TaskApproved;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\InsufficientFundsException;
use App\Models\Task;
use App\Models\TaskBatch;
use App\Models\TaskClaim;
use App\Models\User;
use App\Notifications\AdminAlertNotification;
use App\Notifications\ClaimCancelledNotification;
use App\Notifications\TaskCompletedNotification;
use App\Notifications\TaskRejectedNotification;
use App\Support\AdminNotifier;
use App\Support\Money;
use App\TaskTypes\TaskTypeRegistry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Task lifecycle + escrow accounting.
 *
 * Escrow model: when a requester creates a task, the full budget
 * (reward + platform fee) × slots moves from their available balance into
 * their pending (escrow) bucket and is tracked on tasks.escrow_balance.
 * Each approval pays the developer and the platform out of that escrow.
 * Unused escrow is refunded when a task closes.
 */
class TaskService
{
    public function __construct(
        private readonly WalletService $wallets,
        private readonly TaskPricingService $pricing,
        private readonly AttachmentService $attachments,
        private readonly ActivityLogger $logger,
        private readonly TaskTypeRegistry $types,
    ) {}

    /**
     * @param  array  $data  validated input (see StoreTaskRequest)
     * @param  UploadedFile[]  $files
     */
    public function create(User $requester, array $data, array $files = []): Task
    {
        $this->assertRequester($requester);
        $quote = $this->pricing->quote(Money::of((string) $data['reward']), (int) $data['slots']);

        $task = DB::transaction(function () use ($requester, $data, $quote, $files) {
            $task = $this->newTask($requester, $data, $quote);
            $task->save();
            $this->fundTask($task, $requester, $quote->total());

            foreach ($files as $file) {
                $this->attachments->store($file, $task, $requester);
            }

            return $task;
        });

        $this->logger->log('task.created', $task, ['total' => $quote->total()->toDecimal()], $requester);
        AdminNotifier::send(new AdminAlertNotification(
            'New task awaiting approval', "“{$task->title}” by {$requester->name}", route('admin.tasks.show', $task), 'task',
        ));

        return $task;
    }

    /**
     * Create many similar tasks from one definition. Each item is either a plain
     * line (fills the type's primary field) or a JSON object merged into the payload.
     */
    public function createBatch(User $requester, array $data, array $items): TaskBatch
    {
        $this->assertRequester($requester);
        $max = (int) config('platform.batch.max_items');
        if (count($items) === 0 || count($items) > $max) {
            throw BusinessRuleException::make("A batch must contain between 1 and {$max} items.");
        }

        $handler = $this->types->get($data['type']);
        $slots = (int) $data['slots'];
        $quote = $this->pricing->quote(Money::of((string) $data['reward']), $slots);
        $grandTotal = $quote->total()->multiply(count($items));

        $batch = DB::transaction(function () use ($requester, $data, $items, $handler, $quote, $slots, $grandTotal) {
            $batch = TaskBatch::query()->create([
                'requester_id' => $requester->id,
                'category_id' => $data['category_id'],
                'title' => $data['title'],
                'type' => $data['type'],
                'total_tasks' => count($items),
                'slots_per_task' => $slots,
                'reward' => $quote->reward,
                'platform_fee' => $quote->platformFee,
                'total_budget' => $grandTotal,
                'status' => BatchStatus::PendingApproval,
            ]);

            $wallet = $this->wallets->walletFor($requester);
            $this->wallets->moveBetweenBuckets($wallet, WalletBucket::Available, $grandTotal, TransactionType::TaskEscrow,
                "Budget reserved for batch “{$batch->title}” (".count($items).' tasks)', $batch, $requester);

            foreach (array_values($items) as $i => $item) {
                $payload = $data['payload'] ?? [];
                if (is_array($item)) {
                    $payload = array_merge($payload, $item);
                } else {
                    $payload[$handler->batchField()] = $item;
                }
                $taskData = $data;
                $taskData['payload'] = $payload;
                $taskData['title'] = $data['title'].' #'.($i + 1);

                $task = $this->newTask($requester, $taskData, $quote);
                $task->batch_id = $batch->id;
                $task->escrow_balance = $quote->total();
                $task->save();
            }

            return $batch;
        });

        $this->logger->log('batch.created', $batch, ['tasks' => count($items), 'total' => $grandTotal->toDecimal()], $requester);
        AdminNotifier::send(new AdminAlertNotification(
            'New task batch awaiting approval', "“{$batch->title}” — ".count($items)." tasks by {$requester->name}",
            route('admin.tasks.index', ['batch' => $batch->id]), 'task',
        ));

        return $batch;
    }

    /** Requesters may edit a task only before it goes live (or after a rejection). */
    public function update(Task $task, array $data): Task
    {
        if (! in_array($task->status, [TaskStatus::PendingApproval, TaskStatus::Rejected], true) || $task->batch_id) {
            throw BusinessRuleException::make('Only single tasks that are pending approval or rejected can be edited.');
        }

        DB::transaction(function () use ($task, $data) {
            $task = Task::query()->lockForUpdate()->findOrFail($task->id);
            $requester = $task->requester;
            $this->refundEscrow($task, $task->escrow_balance, 'Budget released for task edit');

            $quote = $this->pricing->quote(Money::of((string) $data['reward']), (int) $data['slots']);
            $this->applyQuote($task->fill($this->taskAttributes($data)), $quote);
            $task->status = TaskStatus::PendingApproval;
            $task->rejection_reason = null;
            $task->save();
            $this->fundTask($task, $requester, $quote->total());
        });

        $this->logger->log('task.updated', $task);

        return $task->refresh();
    }

    public function approve(Task $task, User $admin): Task
    {
        DB::transaction(function () use ($task, $admin) {
            $locked = Task::query()->lockForUpdate()->findOrFail($task->id);
            if ($locked->status !== TaskStatus::PendingApproval) {
                throw BusinessRuleException::make('Only tasks pending approval can be approved.');
            }
            $locked->forceFill([
                'status' => TaskStatus::Active,
                'approved_at' => now(),
                'approved_by' => $admin->id,
                'published_at' => now(),
                'rejection_reason' => null,
            ])->save();
            $this->syncBatchStatus($locked);
        });

        $task->refresh();
        $this->logger->log('task.approved', $task, [], $admin);
        event(new TaskApproved($task));

        return $task;
    }

    public function reject(Task $task, User $admin, string $reason): Task
    {
        DB::transaction(function () use ($task, $reason) {
            $locked = Task::query()->lockForUpdate()->findOrFail($task->id);
            if ($locked->status !== TaskStatus::PendingApproval) {
                throw BusinessRuleException::make('Only tasks pending approval can be rejected.');
            }
            $locked->forceFill(['status' => TaskStatus::Rejected, 'rejection_reason' => $reason])->save();
            $this->settleEscrow($locked);
            $this->syncBatchStatus($locked);
        });

        $task->refresh();
        $this->logger->log('task.rejected', $task, ['reason' => $reason], $admin);
        $task->requester->notify(new TaskRejectedNotification($task));

        return $task;
    }

    /** Admin takes a live task offline (policy violation, investigation...). */
    public function suspend(Task $task, User $admin, string $reason): Task
    {
        DB::transaction(function () use ($task, $reason) {
            $locked = Task::query()->lockForUpdate()->findOrFail($task->id);
            if (! in_array($locked->status, [TaskStatus::Active, TaskStatus::Paused, TaskStatus::PendingApproval], true)) {
                throw BusinessRuleException::make('This task cannot be suspended in its current state.');
            }
            $locked->forceFill(['status' => TaskStatus::Suspended, 'rejection_reason' => $reason])->save();
            $this->cancelActiveClaims($locked, 'The task was suspended by an administrator.');
        });

        $this->logger->log('task.suspended', $task, ['reason' => $reason], $admin);

        return $task->refresh();
    }

    public function reinstate(Task $task, User $admin): Task
    {
        $this->transition($task, [TaskStatus::Suspended], TaskStatus::Active, fn (Task $t) => $t->rejection_reason = null);
        $this->logger->log('task.reinstated', $task, [], $admin);

        return $task->refresh();
    }

    public function pause(Task $task): Task
    {
        $this->transition($task, [TaskStatus::Active], TaskStatus::Paused);
        $this->logger->log('task.paused', $task);

        return $task->refresh();
    }

    public function resume(Task $task): Task
    {
        $this->transition($task, [TaskStatus::Paused], TaskStatus::Active);
        $this->logger->log('task.resumed', $task);

        return $task->refresh();
    }

    public function cancel(Task $task, User $actor): Task
    {
        DB::transaction(function () use ($task) {
            $locked = Task::query()->lockForUpdate()->findOrFail($task->id);
            if ($locked->status->isClosed()) {
                throw BusinessRuleException::make('This task is already closed.');
            }
            $locked->status = TaskStatus::Cancelled;
            $locked->save();
            $this->cancelActiveClaims($locked, 'The requester cancelled this task.');
            $this->settleEscrow($locked);
            $this->syncBatchStatus($locked);
        });

        $this->logger->log('task.cancelled', $task, [], $actor);

        return $task->refresh();
    }

    /** Add more worker slots to a task, funding them immediately. */
    public function addSlots(Task $task, int $slots): Task
    {
        if ($slots < 1 || $slots > 10000) {
            throw BusinessRuleException::make('Choose between 1 and 10,000 additional slots.');
        }

        DB::transaction(function () use ($task, $slots) {
            $locked = Task::query()->lockForUpdate()->findOrFail($task->id);
            if (! in_array($locked->status, [TaskStatus::Active, TaskStatus::Paused, TaskStatus::Completed, TaskStatus::PendingApproval], true)) {
                throw BusinessRuleException::make('Slots can only be added to open or completed tasks.');
            }
            $amount = $locked->unitCost()->multiply($slots);
            $this->fundTask($locked, $locked->requester, $amount, "Added {$slots} slot(s) to “{$locked->title}”");
            $locked->available_slots += $slots;
            $locked->total_budget = $locked->total_budget->add($amount);
            if ($locked->status === TaskStatus::Completed) {
                $locked->status = TaskStatus::Active;
            }
            $locked->save();
        });

        $this->logger->log('task.slots_added', $task, ['slots' => $slots]);

        return $task->refresh();
    }

    /** Close tasks whose deadline passed. Claims already in progress may still finish. */
    public function closeExpired(): int
    {
        $count = 0;
        Task::query()
            ->whereIn('status', [TaskStatus::Active->value, TaskStatus::Paused->value])
            ->whereNotNull('deadline')->where('deadline', '<', now())
            ->each(function (Task $task) use (&$count) {
                DB::transaction(function () use ($task) {
                    $locked = Task::query()->lockForUpdate()->find($task->id);
                    if ($locked && ! $locked->status->isClosed()) {
                        $locked->status = TaskStatus::Expired;
                        $locked->save();
                        $this->settleEscrow($locked);
                    }
                });
                $count++;
            });

        return $count;
    }

    // ---------------------------------------------------------------------
    // Slot + escrow primitives (callers hold a lock on the task row)
    // ---------------------------------------------------------------------

    /** A reserved slot (active claim / pending submission) was given back. */
    public function releaseSlot(Task $lockedTask): void
    {
        $lockedTask->reserved_slots = max(0, $lockedTask->reserved_slots - 1);
        $lockedTask->save();
        $this->settleEscrow($lockedTask);
    }

    /** Mark the task completed when every slot has an approved submission. */
    public function completeIfFull(Task $lockedTask): bool
    {
        if ($lockedTask->completed_slots >= $lockedTask->available_slots && $lockedTask->status === TaskStatus::Active) {
            $lockedTask->status = TaskStatus::Completed;
            $lockedTask->save();
            $this->syncBatchStatus($lockedTask);
            DB::afterCommit(fn () => $lockedTask->requester->notify(new TaskCompletedNotification($lockedTask)));

            return true;
        }

        return false;
    }

    /**
     * For closed tasks, refund any escrow not needed by slots still in flight.
     */
    public function settleEscrow(Task $lockedTask): void
    {
        if (! $lockedTask->status->isClosed()) {
            return;
        }
        $stillNeeded = $lockedTask->unitCost()->multiply($lockedTask->reserved_slots);
        $refundable = $lockedTask->escrow_balance->subtract($stillNeeded);
        if ($refundable->isPositive()) {
            $this->refundEscrow($lockedTask, $refundable, "Unused budget refunded for “{$lockedTask->title}”");
        }
    }

    private function refundEscrow(Task $lockedTask, Money $amount, string $description): void
    {
        if (! $amount->isPositive()) {
            return;
        }
        $wallet = $this->wallets->walletFor($lockedTask->requester);
        $this->wallets->post($wallet, TransactionType::Refund, WalletBucket::Pending, $amount->negate(), $description, $lockedTask);
        $this->wallets->post($wallet, TransactionType::Refund, WalletBucket::Available, $amount, $description, $lockedTask);
        $lockedTask->escrow_balance = $lockedTask->escrow_balance->subtract($amount);
        $lockedTask->save();
    }

    private function fundTask(Task $task, User $requester, Money $amount, ?string $description = null): void
    {
        $wallet = $this->wallets->walletFor($requester);
        if ($wallet->balance->lessThan($amount)) {
            throw new InsufficientFundsException(
                'Insufficient balance: this requires '.$amount->format().' but your available balance is '.$wallet->balance->format().'. Add funds first.'
            );
        }
        $this->wallets->moveBetweenBuckets($wallet, WalletBucket::Available, $amount, TransactionType::TaskEscrow,
            $description ?? "Budget reserved for “{$task->title}”", $task, $requester);
        $task->escrow_balance = $task->escrow_balance->add($amount);
        $task->save();
    }

    private function cancelActiveClaims(Task $lockedTask, string $reason): void
    {
        $lockedTask->claims()->where('status', ClaimStatus::Active->value)->lockForUpdate()->get()
            ->each(function (TaskClaim $claim) use ($lockedTask, $reason) {
                $claim->forceFill(['status' => ClaimStatus::Cancelled, 'finished_at' => now()])->save();
                $lockedTask->reserved_slots = max(0, $lockedTask->reserved_slots - 1);
                DB::afterCommit(fn () => $claim->developer->notify(new ClaimCancelledNotification($claim, $reason)));
            });
        $lockedTask->save();
    }

    private function transition(Task $task, array $from, TaskStatus $to, ?callable $mutate = null): void
    {
        DB::transaction(function () use ($task, $from, $to, $mutate) {
            $locked = Task::query()->lockForUpdate()->findOrFail($task->id);
            if (! in_array($locked->status, $from, true)) {
                throw BusinessRuleException::make("This task cannot be moved to “{$to->label()}” from “{$locked->status->label()}”.");
            }
            $locked->status = $to;
            if ($mutate) {
                $mutate($locked);
            }
            $locked->save();
        });
    }

    private function newTask(User $requester, array $data, PriceQuote $quote): Task
    {
        $task = new Task($this->taskAttributes($data));
        $task->requester_id = $requester->id;
        $task->status = TaskStatus::PendingApproval;
        $task->escrow_balance = Money::zero();
        $task->reserved_slots = 0;
        $task->completed_slots = 0;
        $this->applyQuote($task, $quote);

        return $task;
    }

    private function applyQuote(Task $task, PriceQuote $quote): void
    {
        $task->reward = $quote->reward;
        $task->platform_fee = $quote->platformFee;
        $task->commission_percent = $quote->commissionPercent;
        $task->available_slots = $quote->slots;
        $task->total_budget = $quote->total();
    }

    private function taskAttributes(array $data): array
    {
        $handler = $this->types->get($data['type']);

        return [
            'category_id' => $data['category_id'],
            'template_id' => $data['template_id'] ?? null,
            'type' => $handler->key(),
            'title' => $data['title'],
            'description' => $data['description'],
            'instructions' => $data['instructions'],
            'payload' => $data['payload'] ?? [],
            'answer_format' => $data['answer_format'] ?? null,
            'estimated_minutes' => (int) $data['estimated_minutes'],
            'difficulty' => $data['difficulty'],
            'required_skills' => array_values(array_filter($data['required_skills'] ?? [])),
            'deadline' => $data['deadline'] ?? null,
        ];
    }

    private function syncBatchStatus(Task $task): void
    {
        if (! $task->batch_id) {
            return;
        }
        $batch = TaskBatch::query()->find($task->batch_id);
        $statuses = Task::query()->where('batch_id', $batch->id)->pluck('status')->map(fn ($s) => $s->value);

        $status = match (true) {
            $statuses->contains(TaskStatus::PendingApproval->value) => BatchStatus::PendingApproval,
            $statuses->contains(TaskStatus::Active->value) || $statuses->contains(TaskStatus::Paused->value) => BatchStatus::Active,
            $statuses->every(fn ($s) => $s === TaskStatus::Rejected->value) => BatchStatus::Rejected,
            $statuses->every(fn ($s) => $s === TaskStatus::Cancelled->value) => BatchStatus::Cancelled,
            default => BatchStatus::Completed,
        };
        $batch->update(['status' => $status]);
    }

    private function assertRequester(User $user): void
    {
        if (! $user->isRequester() && ! $user->isAdmin()) {
            throw BusinessRuleException::make('Only requester accounts can post tasks.', 'forbidden', 403);
        }
        if ($user->isSuspended()) {
            throw BusinessRuleException::make('Your account is suspended.', 'suspended', 403);
        }
    }
}
