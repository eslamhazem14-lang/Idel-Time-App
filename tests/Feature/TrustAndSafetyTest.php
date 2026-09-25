<?php

namespace Tests\Feature;

use App\Enums\DisputeStatus;
use App\Enums\FraudFlagType;
use App\Enums\SubmissionStatus;
use App\Models\Dispute;
use App\Models\FraudFlag;
use App\Models\TaskSubmission;
use App\Services\SubmissionReviewService;
use App\Services\TaskClaimService;
use App\Services\TaskSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TrustAndSafetyTest extends TestCase
{
    use RefreshDatabase;

    private function submit($task, $dev, array $answer, int $seconds = 180): TaskSubmission
    {
        $claim = app(TaskClaimService::class)->claim($task, $dev);
        $this->travel($seconds)->seconds();

        return app(TaskSubmissionService::class)->submit($claim, $answer);
    }

    public function test_developer_appeals_and_admin_upholds(): void
    {
        $task = $this->activeTask(null, ['slots' => 1]);
        $dev = $this->developer();
        $submission = $this->submit($task, $dev, ['text' => 'A correct answer that was rejected.']);
        app(SubmissionReviewService::class)->reject($submission, $task->requester, 'Wrong answer, sorry about that.');

        $this->actingAs($dev)->post(route('developer.submissions.appeal', $submission), ['reason' => 'too short'])->assertSessionHasErrors('reason');
        $this->actingAs($dev)->post(route('developer.submissions.appeal', $submission), ['reason' => 'My answer matches the instructions exactly; please re-check.'])->assertSessionHas('success');
        $dispute = Dispute::query()->firstOrFail();

        $this->actingAs($this->admin())->post(route('admin.disputes.resolve', $dispute), ['decision' => 'uphold', 'note' => 'Answer is correct, reviewer mistake.'])->assertRedirect();

        $this->assertSame(DisputeStatus::Upheld, $dispute->fresh()->status);
        $this->assertSame(SubmissionStatus::Approved, $submission->fresh()->status);
        $this->assertSame('0.50', $this->wallet($dev)->balance->toDecimal());
        $this->assertLedgerReconciles();
    }

    public function test_only_rejected_own_submissions_can_be_appealed(): void
    {
        $task = $this->activeTask();
        $submission = $this->submit($task, $this->developer(), ['text' => 'Pending answer here.']);

        $this->actingAs($this->developer())->post(route('developer.submissions.appeal', $submission), ['reason' => str_repeat('x', 30)])->assertForbidden();
        $this->actingAs($submission->developer)->post(route('developer.submissions.appeal', $submission), ['reason' => str_repeat('x', 30)])
            ->assertSessionHas('error', 'Only rejected submissions can be appealed.');
    }

    public function test_suspiciously_fast_completion_is_flagged_not_banned(): void
    {
        $task = $this->activeTask(null, ['estimated_minutes' => 10]);
        $dev = $this->developer();
        $submission = $this->submit($task, $dev, ['text' => 'fast answer'], 5);

        $this->assertTrue($submission->fresh()->is_flagged);
        $this->assertDatabaseHas('fraud_flags', ['user_id' => $dev->id, 'type' => FraudFlagType::FastCompletion->value]);
        $this->assertSame(10, $dev->fresh()->fraud_score);
        $this->assertFalse($dev->fresh()->isSuspended());
    }

    public function test_duplicate_answers_on_the_same_task_are_flagged(): void
    {
        $task = $this->activeTask(null, ['slots' => 2]);
        $this->submit($task, $this->developer(), ['text' => 'Identical copied answer text']);
        $copier = $this->developer();
        $this->submit($task, $copier, ['text' => '  identical COPIED answer   text ']);

        $this->assertDatabaseHas('fraud_flags', ['user_id' => $copier->id, 'type' => FraudFlagType::DuplicateSubmission->value]);
    }

    public function test_repeated_answers_across_tasks_are_flagged(): void
    {
        $dev = $this->developer();
        foreach (range(1, 3) as $i) {
            $this->submit($this->activeTask(), $dev, ['text' => 'Same lazy answer every time']);
        }
        $this->assertDatabaseHas('fraud_flags', ['user_id' => $dev->id, 'type' => FraudFlagType::RepeatedAnswers->value]);
    }

    public function test_similar_email_registration_is_flagged(): void
    {
        Notification::fake();
        $this->developer(['email' => 'janedoe@gmail.com']);
        $this->post('/register', ['name' => 'Jane', 'email' => 'jane.doe+2@gmail.com', 'password' => 'password123', 'password_confirmation' => 'password123', 'role' => 'developer', 'terms' => '1']);

        $this->assertSame(1, FraudFlag::query()->where('type', FraudFlagType::SimilarEmail->value)->count());
    }

    public function test_admin_can_dismiss_flags_and_suspend_users(): void
    {
        $task = $this->activeTask(null, ['estimated_minutes' => 10]);
        $dev = $this->developer();
        $this->submit($task, $dev, ['text' => 'fast'], 2);
        $flag = FraudFlag::query()->firstOrFail();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.fraud.update', $flag), ['status' => 'dismissed'])->assertRedirect();
        $this->assertSame(0, $dev->fresh()->fraud_score);

        $this->actingAs($admin)->post(route('admin.users.suspend', $dev), ['reason' => 'Confirmed abuse'])->assertRedirect();
        $this->assertTrue($dev->fresh()->isSuspended());
        $this->actingAs($admin)->post(route('admin.users.reactivate', $dev))->assertRedirect();
        $this->assertFalse($dev->fresh()->isSuspended());
    }

    public function test_developer_can_report_task_and_rate_it(): void
    {
        $task = $this->activeTask();
        $dev = $this->developer();
        $this->actingAs($dev)->post(route('developer.tasks.report', $task), ['reason' => 'unclear_instructions', 'description' => 'Step 2 is missing.'])->assertSessionHas('success');
        $this->assertDatabaseHas('reports', ['task_id' => $task->id, 'reason' => 'unclear_instructions']);

        $this->actingAs($dev)->post(route('developer.tasks.review', $task), ['rating' => 5])->assertForbidden();
        $this->submit($task, $dev, ['text' => 'An answer to rate after.']);
        $this->actingAs($dev)->post(route('developer.tasks.review', $task), ['rating' => 4, 'time_accurate' => '1'])->assertSessionHas('success');
        $this->assertDatabaseHas('task_reviews', ['task_id' => $task->id, 'rating' => 4]);
    }
}
