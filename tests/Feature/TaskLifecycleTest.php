<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Notifications\AdminAlertNotification;
use App\Notifications\TaskApprovedNotification;
use App\Notifications\TaskRejectedNotification;
use App\Services\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TaskLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_requester_creates_task_and_budget_is_escrowed(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $requester = $this->requester('10.00');

        $this->actingAs($requester)->post(route('requester.tasks.store'), $this->taskData(['reward' => '0.50', 'slots' => 4]))
            ->assertRedirect();

        $task = Task::query()->firstOrFail();
        $this->assertSame(TaskStatus::PendingApproval, $task->status);
        $this->assertSame('0.15', $task->platform_fee->toDecimal());
        $this->assertSame('2.60', $task->total_budget->toDecimal()); // (0.50 + 0.15) × 4
        $this->assertSame('2.60', $task->escrow_balance->toDecimal());

        $wallet = $this->wallet($requester);
        $this->assertSame('7.40', $wallet->balance->toDecimal());
        $this->assertSame('2.60', $wallet->pending_balance->toDecimal());
        Notification::assertSentTo($admin, AdminAlertNotification::class);
        $this->assertLedgerReconciles();
    }

    public function test_client_supplied_financial_fields_are_ignored(): void
    {
        $requester = $this->requester('10.00');
        $this->actingAs($requester)->post(route('requester.tasks.store'), $this->taskData([
            'platform_fee' => '0.00', 'total_budget' => '0.01', 'escrow_balance' => '999', 'status' => 'active', 'completed_slots' => 5,
        ]));

        $task = Task::query()->firstOrFail();
        $this->assertSame('0.15', $task->platform_fee->toDecimal());
        $this->assertSame('1.30', $task->total_budget->toDecimal());
        $this->assertSame(TaskStatus::PendingApproval, $task->status);
        $this->assertSame(0, $task->completed_slots);
    }

    public function test_commission_is_configurable(): void
    {
        settings()->set('commission_percent', '20.00');
        $task = $this->pendingTask(null, ['reward' => '1.00', 'slots' => 1]);
        $this->assertSame('0.20', $task->platform_fee->toDecimal());
        $this->assertSame('20.00', $task->commission_percent);
    }

    public function test_task_creation_fails_without_funds(): void
    {
        $requester = $this->requester('0.00');
        $this->actingAs($requester)->from(route('requester.tasks.create'))
            ->post(route('requester.tasks.store'), $this->taskData())
            ->assertRedirect(route('requester.tasks.create'))
            ->assertSessionHas('error');
        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_task_validation_uses_type_rules_and_limits(): void
    {
        $requester = $this->requester();
        $this->actingAs($requester)->post(route('requester.tasks.store'), $this->taskData([
            'type' => 'multiple_choice', 'payload' => ['question' => 'Pick', 'options' => 'only one'], 'estimated_minutes' => 90, 'reward' => '0.001',
        ]))->assertSessionHasErrors(['payload.options', 'estimated_minutes', 'reward']);

        $this->actingAs($requester)->post(route('requester.tasks.store'), $this->taskData([
            'type' => 'website_qa', 'payload' => ['url' => 'javascript:alert(1)', 'test_steps' => 'x'],
        ]))->assertSessionHasErrors(['payload.url']);
    }

    public function test_multiple_choice_options_are_parsed_from_lines(): void
    {
        $requester = $this->requester();
        $this->actingAs($requester)->post(route('requester.tasks.store'), $this->taskData([
            'type' => 'multiple_choice', 'payload' => ['question' => 'Pick one', 'options' => "A\nB\n\nC", 'multiple' => '0'],
        ]))->assertSessionHasNoErrors();

        $this->assertSame(['A', 'B', 'C'], Task::query()->first()->payload['options']);
    }

    public function test_admin_approves_task(): void
    {
        Notification::fake();
        $task = $this->pendingTask();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.tasks.approve', $task))->assertRedirect();

        $task->refresh();
        $this->assertSame(TaskStatus::Active, $task->status);
        $this->assertSame($admin->id, $task->approved_by);
        $this->assertNotNull($task->published_at);
        Notification::assertSentTo($task->requester, TaskApprovedNotification::class);
    }

    public function test_admin_rejection_requires_reason_and_refunds(): void
    {
        Notification::fake();
        $requester = $this->requester('10.00');
        $task = $this->pendingTask($requester);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.tasks.reject', $task), ['reason' => ''])->assertSessionHasErrors('reason');
        $this->actingAs($admin)->post(route('admin.tasks.reject', $task), ['reason' => 'Instructions are unclear.'])->assertRedirect();

        $this->assertSame(TaskStatus::Rejected, $task->fresh()->status);
        $wallet = $this->wallet($requester);
        $this->assertSame('10.00', $wallet->balance->toDecimal());
        $this->assertSame('0.00', $wallet->pending_balance->toDecimal());
        Notification::assertSentTo($requester, TaskRejectedNotification::class);
        $this->assertLedgerReconciles();
    }

    public function test_requester_can_cancel_and_unused_budget_is_refunded(): void
    {
        $requester = $this->requester('10.00');
        $task = $this->activeTask($requester);

        $this->actingAs($requester)->post(route('requester.tasks.cancel', $task))->assertRedirect();

        $this->assertSame(TaskStatus::Cancelled, $task->fresh()->status);
        $this->assertSame('10.00', $this->wallet($requester)->balance->toDecimal());
        $this->assertLedgerReconciles();
    }

    public function test_requester_can_add_slots_and_edit_pending_tasks_only(): void
    {
        $requester = $this->requester('10.00');
        $task = $this->activeTask($requester, ['slots' => 1]);
        $this->actingAs($requester)->post(route('requester.tasks.slots', $task), ['slots' => 2])->assertRedirect();
        $this->assertSame(3, $task->fresh()->available_slots);
        $this->assertSame('1.95', $task->fresh()->escrow_balance->toDecimal());

        $this->actingAs($requester)->get(route('requester.tasks.edit', $task))->assertForbidden();

        $pending = $this->pendingTask($requester, ['slots' => 1]);
        $this->actingAs($requester)->put(route('requester.tasks.update', $pending), $this->taskData(['slots' => 3, 'title' => 'Updated title']))->assertRedirect();
        $this->assertSame('Updated title', $pending->fresh()->title);
        $this->assertSame('1.95', $pending->fresh()->escrow_balance->toDecimal());
        $this->assertLedgerReconciles();
    }

    public function test_batch_creates_many_tasks(): void
    {
        $requester = $this->requester('50.00');
        $data = $this->taskData(['slots' => 1, 'reward' => '0.20']);
        unset($data['payload']);
        $data['items'] = "Question one?\nQuestion two?\n{\"question\": \"Question three?\"}";

        $this->actingAs($requester)->post(route('requester.batches.store'), $data)->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame(3, Task::query()->count());
        $this->assertSame('Question three?', Task::query()->orderByDesc('id')->first()->payload['question']);
        $this->assertSame('0.78', $this->wallet($requester)->pending_balance->toDecimal()); // (0.20+0.06)×3

        $batch = Task::query()->first()->batch;
        $this->actingAs($this->admin())->post(route('admin.batches.approve', $batch))->assertRedirect();
        $this->assertSame(3, Task::query()->where('status', 'active')->count());
        $this->assertLedgerReconciles();
    }

    public function test_deadline_passed_closes_task_and_refunds(): void
    {
        $requester = $this->requester('10.00');
        $task = $this->activeTask($requester, ['deadline' => now()->addDay()->toDateTimeString()]);
        $this->travel(2)->days();

        app(TaskService::class)->closeExpired();

        $this->assertSame(TaskStatus::Expired, $task->fresh()->status);
        $this->assertSame('10.00', $this->wallet($requester)->balance->toDecimal());
    }
}
