<?php

namespace Tests\Feature;

use App\Enums\ClaimStatus;
use App\Exceptions\BusinessRuleException;
use App\Jobs\ExpireStaleClaims;
use App\Models\TaskClaim;
use App\Models\User;
use App\Notifications\ClaimExpiredNotification;
use App\Services\TaskClaimService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ClaimTest extends TestCase
{
    use RefreshDatabase;

    public function test_developer_claims_task_and_slot_is_locked(): void
    {
        $task = $this->activeTask(null, ['slots' => 1, 'estimated_minutes' => 5]);
        $dev = $this->developer();

        $response = $this->actingAs($dev)->post(route('developer.tasks.claim', $task));

        $claim = TaskClaim::query()->firstOrFail();
        $response->assertRedirect(route('developer.work.show', $claim));
        $this->assertSame(ClaimStatus::Active, $claim->status);
        // server-authoritative window: 5 min × 2 = 10 min
        $this->assertEqualsWithDelta(600, $claim->claimed_at->diffInSeconds($claim->expires_at), 1);
        $this->assertSame(1, $task->fresh()->reserved_slots);
        $this->assertSame(0, $task->fresh()->remainingSlots());

        $this->actingAs($dev)->get(route('developer.work.show', $claim))->assertOk()->assertSee('Time left');
    }

    public function test_same_developer_cannot_claim_twice(): void
    {
        $task = $this->activeTask(null, ['slots' => 3]);
        $dev = $this->developer();
        $service = app(TaskClaimService::class);
        $claim = $service->claim($task, $dev);
        $service->release($claim);

        $this->actingAs($dev)->from(route('developer.tasks.show', $task))->post(route('developer.tasks.claim', $task))
            ->assertSessionHas('error', 'You have already worked on this task.');
        $this->assertSame(1, TaskClaim::query()->count());
    }

    public function test_full_task_cannot_be_claimed_by_another_developer(): void
    {
        $task = $this->activeTask(null, ['slots' => 1]);
        app(TaskClaimService::class)->claim($task, $this->developer());

        $this->actingAs($this->developer())->from('/tasks')->post(route('developer.tasks.claim', $task))
            ->assertSessionHas('error', 'This task was just claimed by another developer.');
    }

    public function test_developer_can_hold_only_one_active_claim_by_default(): void
    {
        $dev = $this->developer();
        $service = app(TaskClaimService::class);
        $service->claim($this->activeTask(), $dev);

        $this->expectException(BusinessRuleException::class);
        $service->claim($this->activeTask(), $dev);
    }

    public function test_pending_tasks_cannot_be_claimed(): void
    {
        $this->expectException(BusinessRuleException::class);
        app(TaskClaimService::class)->claim($this->pendingTask(), $this->developer());
    }

    public function test_unverified_developer_cannot_claim(): void
    {
        $task = $this->activeTask();
        $dev = User::factory()->developer()->unverified()->create();
        $this->actingAs($dev)->post(route('developer.tasks.claim', $task))->assertRedirect(route('verification.notice'));
        $this->assertDatabaseCount('task_claims', 0);
    }

    public function test_expired_claims_release_their_slot(): void
    {
        Notification::fake();
        $task = $this->activeTask(null, ['slots' => 1, 'estimated_minutes' => 5]);
        $dev = $this->developer();
        $claim = app(TaskClaimService::class)->claim($task, $dev);

        $this->travel(10)->minutes();
        dispatch_sync(new ExpireStaleClaims);
        $this->assertSame(ClaimStatus::Active, $claim->fresh()->status, 'Grace period should still apply');

        $this->travel(2)->minutes();
        dispatch_sync(new ExpireStaleClaims);

        $this->assertSame(ClaimStatus::Expired, $claim->fresh()->status);
        $this->assertSame(0, $task->fresh()->reserved_slots);
        $this->assertTrue($task->fresh()->isClaimable());
        Notification::assertSentTo($dev, ClaimExpiredNotification::class);
    }

    public function test_stale_claims_are_expired_lazily_when_someone_else_claims(): void
    {
        $task = $this->activeTask(null, ['slots' => 1]);
        app(TaskClaimService::class)->claim($task, $this->developer());
        $this->travel(1)->hour();

        $claim = app(TaskClaimService::class)->claim($task, $this->developer());
        $this->assertSame(ClaimStatus::Active, $claim->status);
        $this->assertSame(1, TaskClaim::query()->where('status', 'expired')->count());
    }

    public function test_submission_after_deadline_is_rejected_server_side(): void
    {
        $task = $this->activeTask(null, ['slots' => 1, 'estimated_minutes' => 5]);
        $dev = $this->developer();
        $claim = app(TaskClaimService::class)->claim($task, $dev);
        $this->travel(15)->minutes();

        $this->actingAs($dev)->post(route('developer.work.submit', $claim), ['answer' => ['text' => 'Too late answer']])
            ->assertSessionHas('error', 'Your task expired.');

        $this->assertSame(ClaimStatus::Expired, $claim->fresh()->status);
        $this->assertDatabaseCount('task_submissions', 0);
        $this->assertSame(0, $task->fresh()->reserved_slots);
    }

    public function test_submission_within_grace_period_is_accepted(): void
    {
        $task = $this->activeTask(null, ['estimated_minutes' => 5]);
        $dev = $this->developer();
        $claim = app(TaskClaimService::class)->claim($task, $dev);
        $this->travel(10 * 60 + 30)->seconds();

        $this->actingAs($dev)->post(route('developer.work.submit', $claim), ['answer' => ['text' => 'Just in time']])->assertRedirect();
        $this->assertDatabaseCount('task_submissions', 1);
    }

    public function test_submit_draft_policy_auto_submits_valid_drafts(): void
    {
        settings()->set('expired_claim_policy', 'submit_draft');
        $task = $this->activeTask(null, ['estimated_minutes' => 5]);
        $dev = $this->developer();
        $claim = app(TaskClaimService::class)->claim($task, $dev);

        $this->actingAs($dev)->postJson(route('developer.work.draft', $claim), ['answer' => ['text' => 'My saved draft']])->assertOk();
        $this->travel(20)->minutes();
        app(TaskClaimService::class)->expireOverdue();

        $this->assertSame(ClaimStatus::Submitted, $claim->fresh()->status);
        $this->assertSame('My saved draft', $claim->submissions()->first()->answer['text']);
    }

    public function test_release_frees_slot(): void
    {
        $task = $this->activeTask(null, ['slots' => 1]);
        $dev = $this->developer();
        $claim = app(TaskClaimService::class)->claim($task, $dev);

        $this->actingAs($dev)->post(route('developer.work.release', $claim))->assertRedirect(route('developer.tasks.index'));
        $this->assertSame(ClaimStatus::Cancelled, $claim->fresh()->status);
        $this->assertSame(1, $task->fresh()->remainingSlots());
    }

    public function test_claiming_is_rate_limited(): void
    {
        $dev = $this->developer();
        $task = $this->activeTask();
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($dev)->post(route('developer.tasks.claim', $task));
        }
        $this->actingAs($dev)->post(route('developer.tasks.claim', $task))->assertStatus(429);
    }
}
