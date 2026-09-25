<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskClaim;
use App\Models\User;
use App\Services\SubmissionReviewService;
use App\Services\TaskClaimService;
use App\Services\TaskService;
use App\Services\TaskSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token_with_role_abilities(): void
    {
        $this->developer(['email' => 'dev@example.test']);

        $response = $this->postJson('/api/auth/login', ['email' => 'dev@example.test', 'password' => 'password', 'device_name' => 'vscode'])
            ->assertOk()->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'role']]);

        $this->withToken($response->json('token'))->getJson('/api/auth/me')->assertOk()->assertJsonPath('data.email', 'dev@example.test');
        $this->postJson('/api/auth/login', ['email' => 'dev@example.test', 'password' => 'bad', 'device_name' => 'x'])->assertUnprocessable();
    }

    public function test_register_via_api(): void
    {
        Notification::fake();
        $this->postJson('/api/auth/register', [
            'name' => 'Api Dev', 'email' => 'api@example.test', 'password' => 'password123', 'password_confirmation' => 'password123',
            'role' => 'developer', 'accept_terms' => true, 'device_name' => 'macos',
        ])->assertCreated()->assertJsonPath('user.role', 'developer')->assertJsonStructure(['token']);
    }

    public function test_unauthenticated_and_wrong_role_requests_are_rejected(): void
    {
        $this->getJson('/api/tasks')->assertUnauthorized();
        Sanctum::actingAs($this->requester());
        $this->getJson('/api/tasks')->assertForbidden();
        $this->getJson('/api/wallet')->assertForbidden();
    }

    public function test_developer_task_flow_over_api(): void
    {
        $task = $this->activeTask(null, ['reward' => '0.50', 'estimated_minutes' => 5, 'slots' => 1]);
        $this->activeTask(null, ['reward' => '2.00', 'estimated_minutes' => 12, 'title' => 'A longer task']);
        $dev = $this->developer();
        Sanctum::actingAs($dev);

        $this->getJson('/api/tasks?max_minutes=8')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $task->id)
            ->assertJsonPath('data.0.reward', '0.50')
            ->assertJsonPath('data.0.reward_per_minute', '$0.100/min')
            ->assertJsonMissingPath('data.0.platform_fee');

        $this->getJson("/api/tasks/{$task->id}")->assertOk()->assertJsonPath('data.content.question', 'What does SELECT 1 return?');

        $claim = $this->postJson("/api/tasks/{$task->id}/claim")->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'expires_at', 'seconds_remaining', 'server_time', 'task']])->json('data');
        $this->postJson("/api/tasks/{$task->id}/claim")->assertStatus(409)->assertJsonPath('code', 'already_claimed');

        $this->postJson("/api/tasks/{$task->id}/submit", ['answer' => []])->assertUnprocessable()->assertJsonValidationErrors('answer.text');
        $this->postJson("/api/tasks/{$task->id}/submit", ['answer' => ['text' => 'It returns a single row containing 1.']])
            ->assertCreated()->assertJsonPath('data.status', 'pending');

        $this->getJson('/api/wallet')->assertOk()->assertJsonPath('data.pending_balance', '0.50')->assertJsonPath('data.available_balance', '0.00');

        $submission = TaskClaim::query()->find($claim['id'])->submissions()->first();
        app(SubmissionReviewService::class)->approve($submission, $task->requester);

        $this->getJson('/api/wallet')->assertJsonPath('data.available_balance', '0.50');
        $this->getJson('/api/wallet/transactions?bucket=available')->assertOk()->assertJsonPath('data.0.amount', '0.50');
        $this->getJson('/api/submissions')->assertOk()->assertJsonPath('data.0.status', 'approved');
        $this->getJson('/api/developer/dashboard')->assertOk()
            ->assertJsonPath('stats.available_balance', '0.50')->assertJsonPath('stats.completed_tasks', 1);
        $this->getJson('/api/notifications')->assertOk()->assertJsonStructure(['data', 'unread_count']);
    }

    public function test_expired_claim_submission_via_api(): void
    {
        $task = $this->activeTask(null, ['estimated_minutes' => 5]);
        $dev = $this->developer();
        app(TaskClaimService::class)->claim($task, $dev);
        $this->travel(30)->minutes();
        Sanctum::actingAs($dev);

        $this->postJson("/api/tasks/{$task->id}/submit", ['answer' => ['text' => 'late answer']])->assertStatus(409)->assertJsonPath('code', 'claim_expired');
        $this->postJson('/api/tasks/'.$this->activeTask()->id.'/submit', ['answer' => ['text' => 'never claimed']])->assertNotFound();
    }

    public function test_withdrawals_via_api(): void
    {
        $dev = $this->developer();
        $this->fund($dev, '20.00');
        Sanctum::actingAs($dev);

        $this->postJson('/api/withdrawals', ['amount' => '5', 'method' => 'paypal', 'account_details' => 'me@example.test'])->assertStatus(422)->assertJsonPath('code', 'business_rule');
        $this->postJson('/api/withdrawals', ['amount' => '15.00', 'method' => 'paypal', 'account_details' => 'me@example.test'])
            ->assertCreated()->assertJsonPath('data.status', 'pending')->assertJsonMissingPath('data.account_details');
        $this->getJson('/api/withdrawals')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/wallet')->assertJsonPath('data.available_balance', '5.00');
    }

    public function test_idle_session_recommends_tasks_that_fit(): void
    {
        $fits = $this->activeTask(null, ['estimated_minutes' => 4, 'reward' => '0.40']);
        $this->activeTask(null, ['estimated_minutes' => 15, 'reward' => '3.00']);
        Sanctum::actingAs($this->developer());

        $session = $this->postJson('/api/idle-sessions', ['client' => 'vscode', 'agent' => 'claude-code', 'expected_minutes' => 8])
            ->assertCreated()
            ->assertJsonPath('session.headline', 'Your AI is working. You have 8 minutes available.')
            ->assertJsonCount(1, 'recommended_tasks')
            ->assertJsonPath('recommended_tasks.0.id', $fits->id)
            ->json('session');

        $this->getJson('/api/tasks/recommended?minutes=20')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/idle-sessions/current')->assertOk()->assertJsonPath('session.id', $session['id']);
        $this->postJson("/api/idle-sessions/{$session['id']}/end")->assertOk()->assertJsonPath('data.minutes_left', 0);
    }

    public function test_requester_api_creates_task_and_reviews(): void
    {
        $requester = $this->requester('20.00');
        Sanctum::actingAs($requester);

        $this->getJson('/api/requester/pricing/quote?reward=0.50&slots=10')->assertOk()
            ->assertJson(['reward' => '0.50', 'platform_fee' => '0.15', 'unit_cost' => '0.65', 'total' => '6.50']);

        $taskId = $this->postJson('/api/requester/tasks', $this->taskData(['total_budget' => '0.01']))
            ->assertCreated()->assertJsonPath('data.status', 'pending_approval')->assertJsonPath('data.total_budget', '1.30')->json('data.id');

        $task = Task::query()->find($taskId);
        app(TaskService::class)->approve($task, $this->admin());
        $claim = app(TaskClaimService::class)->claim($task, $this->developer());
        $submission = app(TaskSubmissionService::class)->submit($claim, ['text' => 'An answer via API flow']);

        $this->getJson("/api/requester/tasks/{$taskId}/submissions")->assertOk()->assertJsonCount(1, 'data');
        $this->postJson("/api/requester/submissions/{$submission->id}/review", ['decision' => 'reject'])->assertJsonValidationErrors('reason');
        $this->postJson("/api/requester/submissions/{$submission->id}/review", ['decision' => 'approve'])->assertOk()->assertJsonPath('data.status', 'approved');

        Sanctum::actingAs($this->requester());
        $this->getJson("/api/requester/tasks/{$taskId}/submissions")->assertForbidden();
    }

    public function test_public_meta_endpoints(): void
    {
        $this->category();
        $this->getJson('/api/categories')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/config')->assertOk()->assertJsonPath('commission_percent', '30.00')->assertJsonCount(7, 'task_types');
    }

    public function test_suspended_tokens_are_rejected(): void
    {
        $dev = User::factory()->developer()->suspended()->create();
        Sanctum::actingAs($dev);
        $this->getJson('/api/wallet')->assertForbidden()->assertJsonPath('code', 'suspended');
    }
}
