<?php

namespace Tests;

use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\User;
use App\Services\SettingsService;
use App\Services\TaskService;
use App\Services\WalletService;
use App\Support\Money;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(SettingsService::class)->flush();
    }

    protected function admin(): User
    {
        return User::factory()->admin()->create();
    }

    protected function developer(array $attributes = []): User
    {
        return User::factory()->developer()->create($attributes);
    }

    protected function requester(string $funds = '100.00', array $attributes = []): User
    {
        $requester = User::factory()->requester()->create($attributes);
        if (Money::of($funds)->isPositive()) {
            $this->fund($requester, $funds);
        }

        return $requester;
    }

    protected function fund(User $user, string $amount): void
    {
        app(WalletService::class)->adjust($user, Money::of($amount), 'Test funds', User::factory()->admin()->create());
    }

    protected function category(): TaskCategory
    {
        return TaskCategory::query()->first() ?? TaskCategory::factory()->create();
    }

    protected function taskData(array $overrides = []): array
    {
        return array_replace_recursive([
            'category_id' => $this->category()->id,
            'type' => 'text_response',
            'title' => 'Explain a SQL query',
            'description' => 'Describe what this SQL query returns.',
            'instructions' => 'Read the query and explain the result in plain English.',
            'estimated_minutes' => 5,
            'reward' => '0.50',
            'slots' => 2,
            'difficulty' => 'easy',
            'required_skills' => ['sql'],
            'payload' => ['question' => 'What does SELECT 1 return?'],
        ], $overrides);
    }

    /** Create a task through the real service (escrow funded). */
    protected function pendingTask(?User $requester = null, array $overrides = []): Task
    {
        return app(TaskService::class)->create($requester ?? $this->requester(), $this->taskData($overrides));
    }

    protected function activeTask(?User $requester = null, array $overrides = []): Task
    {
        $task = $this->pendingTask($requester, $overrides);

        return app(TaskService::class)->approve($task, $this->admin());
    }

    protected function wallet(User $user)
    {
        return app(WalletService::class)->walletFor($user)->refresh();
    }

    protected function assertLedgerReconciles(): void
    {
        $this->assertSame([], app(WalletService::class)->reconcile(), 'Wallet balances do not match the ledger.');
    }
}
