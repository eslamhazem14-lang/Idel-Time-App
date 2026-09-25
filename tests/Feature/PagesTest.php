<?php

namespace Tests\Feature;

use App\Models\Dispute;
use App\Models\Task;
use App\Models\TaskSubmission;
use App\Models\TaskTemplate;
use App\Models\User;
use App\Models\Withdrawal;
use App\Services\TaskClaimService;
use App\TaskTypes\TaskTypeRegistry;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Renders every page with the demo seed data to catch broken views.
 */
class PagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_render_with_seo_tags(): void
    {
        foreach (['/', '/how-it-works', '/developers', '/requesters', '/pricing', '/faq', '/login', '/register', '/forgot-password'] as $url) {
            $this->get($url)->assertOk()->assertSee('<meta name="description"', false)->assertSee('og:title', false);
        }
        $this->get('/')->assertSee('Turn AI Waiting Time Into Money.')->assertSee('Start Earning');
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml')->assertSee(route('pricing'));
        $this->get('/robots.txt')->assertOk()->assertSee('User-agent');
        $this->get('/this-does-not-exist')->assertNotFound()->assertSee('Page not found');
    }

    public function test_all_app_pages_render_with_demo_data(): void
    {
        putenv('SEED_ADMIN_PASSWORD=test-admin-password');
        $this->seed(DatabaseSeeder::class);

        $dev = User::query()->where('email', 'dev1@idletime.test')->first();
        $requester = User::query()->where('email', 'acme@idletime.test')->first();
        $admin = User::query()->where('role', 'admin')->first();

        $devSubmission = $dev->submissions()->first();
        $task = Task::query()->where('status', 'active')->first();
        foreach ([
            '/developer', '/tasks', '/tasks?sort=highest_reward&max_minutes=5', route('developer.tasks.show', $task),
            '/submissions', route('developer.submissions.show', $devSubmission), '/wallet', '/wallet?ledger=pending', '/account', '/notifications',
        ] as $url) {
            $this->actingAs($dev)->get($url)->assertOk();
        }

        $reqTask = $requester->requestedTasks()->first();
        $reqSubmission = TaskSubmission::query()->whereIn('task_id', $requester->requestedTasks()->select('id'))->first();
        foreach ([
            '/requester', '/requester/tasks', '/requester/tasks/create', route('requester.tasks.show', $reqTask), '/requester/batches/create',
            '/requester/submissions', route('requester.submissions.show', $reqSubmission), '/requester/billing',
            route('requester.tasks.create', ['template' => TaskTemplate::query()->first()->id]),
        ] as $url) {
            $this->actingAs($requester)->get($url)->assertOk();
        }

        foreach ([
            '/admin', '/admin/users', route('admin.users.show', $dev), route('admin.users.show', $requester), '/admin/tasks', '/admin/tasks?status=active',
            route('admin.tasks.show', $task), '/admin/submissions', route('admin.submissions.show', TaskSubmission::query()->first()),
            '/admin/withdrawals', route('admin.withdrawals.show', Withdrawal::query()->first()), '/admin/deposits', '/admin/disputes',
            route('admin.disputes.show', Dispute::query()->first()), '/admin/reports', '/admin/fraud', '/admin/categories', '/admin/templates',
            '/admin/templates/create', route('admin.templates.edit', TaskTemplate::query()->first()), '/admin/settings', '/admin/activity',
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }

        $this->assertLedgerReconciles();
    }

    public function test_work_page_renders_for_every_task_type(): void
    {
        $this->seed(DatabaseSeeder::class);
        $service = app(TaskClaimService::class);

        foreach (app(TaskTypeRegistry::class)->keys() as $type) {
            $task = Task::query()->available()->where('type', $type)->first();
            $this->assertNotNull($task, "No seeded task of type {$type}");
            $dev = $this->developer();
            $claim = $service->claim($task, $dev);
            $this->actingAs($dev)->get(route('developer.work.show', $claim))->assertOk()->assertSee('Your answer');
        }
    }
}
