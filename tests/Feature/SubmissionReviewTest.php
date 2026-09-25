<?php

namespace Tests\Feature;

use App\Enums\ClaimStatus;
use App\Enums\SubmissionStatus;
use App\Enums\TaskStatus;
use App\Models\TaskSubmission;
use App\Models\WalletTransaction;
use App\Notifications\NewSubmissionNotification;
use App\Notifications\SubmissionApprovedNotification;
use App\Notifications\SubmissionRejectedNotification;
use App\Services\SubmissionReviewService;
use App\Services\TaskClaimService;
use App\Services\TaskSubmissionService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SubmissionReviewTest extends TestCase
{
    use RefreshDatabase;

    private function submitted(array $taskOverrides = [], array $answer = ['text' => 'A complete and thoughtful answer.']): TaskSubmission
    {
        $task = $this->activeTask($this->requester('10.00'), $taskOverrides);
        $claim = app(TaskClaimService::class)->claim($task, $this->developer());
        $this->travel(3)->minutes();

        return app(TaskSubmissionService::class)->submit($claim, $answer);
    }

    public function test_developer_submits_answer_and_reward_goes_pending(): void
    {
        Notification::fake();
        $task = $this->activeTask();
        $dev = $this->developer();
        $claim = app(TaskClaimService::class)->claim($task, $dev);
        $this->travel(2)->minutes();

        $this->actingAs($dev)->post(route('developer.work.submit', $claim), ['answer' => ['text' => 'SELECT 1 returns one row with the value 1.']])
            ->assertRedirect();

        $submission = TaskSubmission::query()->firstOrFail();
        $this->assertSame(SubmissionStatus::Pending, $submission->status);
        $this->assertSame(120, $submission->time_spent_seconds);
        $this->assertSame(ClaimStatus::Submitted, $claim->fresh()->status);
        $this->assertSame('0.50', $this->wallet($dev)->pending_balance->toDecimal());
        $this->assertSame('0.00', $this->wallet($dev)->balance->toDecimal());
        Notification::assertSentTo($task->requester, NewSubmissionNotification::class);
    }

    public function test_answer_is_validated_per_task_type(): void
    {
        $task = $this->activeTask(null, ['type' => 'ai_evaluation', 'payload' => ['prompt' => 'Q', 'response' => 'A', 'safety_applicable' => '1']]);
        $dev = $this->developer();
        $claim = app(TaskClaimService::class)->claim($task, $dev);

        $this->actingAs($dev)->post(route('developer.work.submit', $claim), ['answer' => ['correctness' => 9, 'relevance' => 3]])
            ->assertSessionHasErrors(['answer.correctness', 'answer.quality', 'answer.safety']);

        $this->actingAs($dev)->post(route('developer.work.submit', $claim), ['answer' => ['correctness' => 4, 'relevance' => 3, 'quality' => 5, 'safety' => 5, 'extra' => 'dropped']])
            ->assertSessionHasNoErrors();
        $this->assertSame(['correctness' => 4, 'relevance' => 3, 'quality' => 5, 'safety' => 5, 'comments' => null], TaskSubmission::query()->first()->answer);
    }

    public function test_cannot_submit_twice(): void
    {
        $submission = $this->submitted();
        $this->actingAs($submission->developer)->post(route('developer.work.submit', $submission->claim), ['answer' => ['text' => 'Again!']])
            ->assertSessionHas('error');
        $this->assertSame(1, TaskSubmission::query()->count());
    }

    public function test_approval_credits_developer_pays_fee_and_spends_escrow(): void
    {
        Notification::fake();
        $submission = $this->submitted(['slots' => 2]);
        $task = $submission->task;
        $requester = $task->requester;

        $this->actingAs($requester)->post(route('requester.submissions.approve', $submission))->assertRedirect();

        $this->assertSame(SubmissionStatus::Approved, $submission->fresh()->status);
        $this->assertSame(ClaimStatus::Approved, $submission->claim->fresh()->status);

        $devWallet = $this->wallet($submission->developer);
        $this->assertSame('0.50', $devWallet->balance->toDecimal());
        $this->assertSame('0.00', $devWallet->pending_balance->toDecimal());
        $this->assertSame('0.50', $devWallet->lifetime_earnings->toDecimal());

        $reqWallet = $this->wallet($requester);
        $this->assertSame('0.65', $reqWallet->pending_balance->toDecimal()); // one slot left in escrow
        $this->assertSame('0.65', $reqWallet->lifetime_spending->toDecimal());
        $this->assertSame('0.15', app(WalletService::class)->platformWallet()->balance->toDecimal());

        $task->refresh();
        $this->assertSame(1, $task->completed_slots);
        $this->assertSame(0, $task->reserved_slots);
        $this->assertSame('0.65', $task->escrow_balance->toDecimal());
        $this->assertSame(1, $submission->developer->developerProfile->fresh()->completed_tasks);

        Notification::assertSentTo($submission->developer, SubmissionApprovedNotification::class);
        $this->assertLedgerReconciles();
    }

    public function test_task_completes_when_all_slots_are_approved(): void
    {
        $submission = $this->submitted(['slots' => 1]);
        app(SubmissionReviewService::class)->approve($submission, $submission->task->requester);
        $this->assertSame(TaskStatus::Completed, $submission->task->fresh()->status);
    }

    public function test_rejection_reverses_pending_and_reopens_slot(): void
    {
        Notification::fake();
        $submission = $this->submitted(['slots' => 1]);
        $requester = $submission->task->requester;

        $this->actingAs($requester)->post(route('requester.submissions.reject', $submission), ['reason' => 'short'])->assertSessionHasErrors('reason');
        $this->actingAs($requester)->post(route('requester.submissions.reject', $submission), ['reason' => 'The answer does not address the question.'])->assertRedirect();

        $this->assertSame(SubmissionStatus::Rejected, $submission->fresh()->status);
        $this->assertSame('0.00', $this->wallet($submission->developer)->pending_balance->toDecimal());
        $this->assertSame('0.00', $this->wallet($submission->developer)->balance->toDecimal());
        $this->assertTrue($submission->task->fresh()->isClaimable());
        Notification::assertSentTo($submission->developer, SubmissionRejectedNotification::class);

        // developer sees reason + appeal option
        $this->actingAs($submission->developer)->get(route('developer.submissions.show', $submission))
            ->assertOk()->assertSee('The answer does not address the question.')->assertSee('Appeal this decision');
        $this->assertLedgerReconciles();
    }

    public function test_a_submission_cannot_be_reviewed_twice(): void
    {
        $submission = $this->submitted();
        $service = app(SubmissionReviewService::class);
        $service->approve($submission, $submission->task->requester);

        $this->actingAs($submission->task->requester)->post(route('requester.submissions.reject', $submission), ['reason' => 'Changed my mind about it.'])
            ->assertSessionHas('error', 'This submission has already been reviewed.');
        $this->assertSame('0.50', $this->wallet($submission->developer)->balance->toDecimal());
    }

    public function test_revision_request_reopens_claim_with_new_timer(): void
    {
        $submission = $this->submitted();
        $requester = $submission->task->requester;

        $this->actingAs($requester)->post(route('requester.submissions.revision', $submission), ['note' => 'Please add an example query.'])->assertRedirect();

        $claim = $submission->claim->fresh();
        $this->assertSame(ClaimStatus::Active, $claim->status);
        $this->assertTrue($claim->expires_at->isFuture());
        $this->assertSame('0.00', $this->wallet($submission->developer)->pending_balance->toDecimal());

        $this->actingAs($submission->developer)->post(route('developer.work.submit', $claim), ['answer' => ['text' => 'Revised answer with SELECT 1 example.']])->assertRedirect();
        $this->assertSame(2, TaskSubmission::query()->count());
        $this->assertSame('0.50', $this->wallet($submission->developer)->pending_balance->toDecimal());
        $this->assertLedgerReconciles();
    }

    public function test_admin_can_override_a_rejection_and_pay(): void
    {
        $submission = $this->submitted(['slots' => 1]);
        $admin = $this->admin();
        app(SubmissionReviewService::class)->reject($submission, $submission->task->requester, 'Not good enough for us.');

        $this->actingAs($admin)->post(route('admin.submissions.override', $submission), ['to' => 'approved', 'note' => 'The answer was actually correct.'])->assertRedirect();

        $this->assertSame(SubmissionStatus::Approved, $submission->fresh()->status);
        $this->assertSame('0.50', $this->wallet($submission->developer)->balance->toDecimal());
        $this->assertLedgerReconciles();
    }

    public function test_admin_can_reverse_an_approval_with_clawback(): void
    {
        $submission = $this->submitted(['slots' => 1]);
        $requester = $submission->task->requester;
        app(SubmissionReviewService::class)->approve($submission, $requester);

        $this->actingAs($this->admin())->post(route('admin.submissions.override', $submission), ['to' => 'rejected', 'note' => 'Plagiarised answer found.'])->assertRedirect();

        $this->assertSame(SubmissionStatus::Rejected, $submission->fresh()->status);
        $this->assertSame('0.00', $this->wallet($submission->developer)->balance->toDecimal());
        $this->assertSame('10.00', $this->wallet($requester)->balance->add($this->wallet($requester)->pending_balance)->toDecimal());
        $this->assertSame('0.00', app(WalletService::class)->platformWallet()->balance->toDecimal());
        $this->assertLedgerReconciles();
    }

    public function test_unreviewed_submissions_are_auto_approved(): void
    {
        $submission = $this->submitted();
        $this->travel(4)->days();
        app(SubmissionReviewService::class)->autoApproveStale();

        $this->assertSame(SubmissionStatus::Approved, $submission->fresh()->status);
        $this->assertTrue($submission->fresh()->auto_approved);
    }

    public function test_ledger_entries_are_immutable(): void
    {
        $this->submitted();
        $tx = WalletTransaction::query()->first();
        $this->expectException(\LogicException::class);
        $tx->update(['description' => 'tampered']);
    }
}
