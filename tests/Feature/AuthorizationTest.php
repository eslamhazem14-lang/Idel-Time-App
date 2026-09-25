<?php

namespace Tests\Feature;

use App\Services\TaskClaimService;
use App\Services\TaskSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        foreach (['/developer', '/tasks', '/wallet', '/requester', '/admin'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_role_areas_are_isolated(): void
    {
        $dev = $this->developer();
        $req = $this->requester();

        $this->actingAs($dev)->get('/requester')->assertForbidden();
        $this->actingAs($dev)->get('/admin')->assertForbidden();
        $this->actingAs($req)->get('/developer')->assertForbidden();
        $this->actingAs($req)->get('/tasks')->assertForbidden();
        $this->actingAs($req)->get('/admin')->assertForbidden();
    }

    public function test_admin_routes_require_admin(): void
    {
        $task = $this->pendingTask();
        $this->actingAs($task->requester)->post(route('admin.tasks.approve', $task))->assertForbidden();
        $this->actingAs($this->developer())->put(route('admin.settings.update'), [])->assertForbidden();
        $this->actingAs($this->admin())->get('/admin')->assertOk();
    }

    public function test_developer_cannot_open_someone_elses_claim(): void
    {
        $task = $this->activeTask();
        $claim = app(TaskClaimService::class)->claim($task, $this->developer());

        $this->actingAs($this->developer())->get(route('developer.work.show', $claim))->assertForbidden();
        $this->actingAs($this->developer())->post(route('developer.work.submit', $claim), ['answer' => ['text' => 'hijack']])->assertForbidden();
    }

    public function test_requester_cannot_review_or_manage_other_requesters_work(): void
    {
        $task = $this->activeTask();
        $claim = app(TaskClaimService::class)->claim($task, $this->developer());
        $submission = app(TaskSubmissionService::class)->submit($claim, ['text' => 'An answer']);
        $other = $this->requester();

        $this->actingAs($other)->get(route('requester.submissions.show', $submission))->assertForbidden();
        $this->actingAs($other)->post(route('requester.submissions.approve', $submission))->assertForbidden();
        $this->actingAs($other)->get(route('requester.tasks.show', $task))->assertForbidden();
        $this->actingAs($other)->post(route('requester.tasks.cancel', $task))->assertForbidden();
    }

    public function test_developers_cannot_see_unapproved_tasks(): void
    {
        $task = $this->pendingTask();
        $this->actingAs($this->developer())->get(route('developer.tasks.show', $task))->assertForbidden();
    }

    public function test_suspended_users_are_logged_out(): void
    {
        $dev = $this->developer();
        $dev->forceFill(['status' => 'suspended'])->save();
        $this->actingAs($dev)->get('/developer')->assertRedirect('/login');
    }
}
