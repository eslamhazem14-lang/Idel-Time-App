<?php

namespace Tests\Feature;

use App\Models\TaskCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function validSettings(array $overrides = []): array
    {
        return array_merge([
            'commission_percent' => '25', 'min_reward' => '0.10', 'max_reward' => '25.00',
            'min_task_minutes' => 1, 'max_task_minutes' => 15, 'claim_duration_multiplier' => '2', 'claim_min_minutes' => 5,
            'claim_grace_seconds' => 60, 'expired_claim_policy' => 'expire', 'max_active_claims' => 1, 'expiry_warning_minutes' => 2,
            'auto_approve_days' => 3, 'min_withdrawal' => '10.00', 'min_deposit' => '10.00', 'withdrawal_methods' => ['paypal'],
            'maintenance_message' => 'Back soon.', 'require_email_verification' => '1', 'ads_enabled' => '1',
            'ad_revenue_share_percent' => '50', 'ad_estimated_view_value' => '0.01', 'ad_min_watch_seconds' => 15, 'ad_daily_cap' => 20, 'ad_cooldown_seconds' => 30,
        ], $overrides);
    }

    public function test_admin_updates_settings(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.update'), $this->validSettings())->assertSessionHas('success');

        $this->assertSame('25.00', settings()->commissionPercent());
        $this->assertSame(['paypal'], settings('withdrawal_methods'));
        $this->assertSame('0.13', $this->pendingTask(null, ['reward' => '0.50'])->platform_fee->toDecimal()); // 0.125 → 0.13
    }

    public function test_settings_are_validated(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.update'), $this->validSettings(['commission_percent' => '150', 'max_reward' => '0.01']))
            ->assertSessionHasErrors(['commission_percent', 'max_reward']);
    }

    public function test_maintenance_mode_blocks_non_admins_but_not_public_pages(): void
    {
        settings()->set('maintenance_mode', true);

        $this->actingAs($this->developer())->get('/developer')->assertStatus(503);
        $this->get('/')->assertOk();
        $this->actingAs($this->admin())->get('/admin')->assertOk();
    }

    public function test_admin_manages_categories_and_templates(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.categories.store'), ['name' => 'Prompt Engineering', 'icon' => 'sparkles', 'is_active' => '1'])->assertSessionHas('success');
        $this->assertDatabaseHas('task_categories', ['slug' => 'prompt-engineering']);

        $category = TaskCategory::query()->where('slug', 'prompt-engineering')->first();
        $this->actingAs($admin)->post(route('admin.templates.store'), [
            'name' => 'Quick AI check', 'category_id' => $category->id, 'type' => 'ai_evaluation', 'title' => 'Rate an AI answer',
            'description' => 'Rate it', 'instructions' => 'Read and rate.', 'estimated_minutes' => 3, 'suggested_reward' => '0.35', 'difficulty' => 'easy', 'is_active' => '1',
        ])->assertRedirect(route('admin.templates.index'));
        $this->assertDatabaseHas('task_templates', ['name' => 'Quick AI check']);

        $this->actingAs($admin)->delete(route('admin.categories.destroy', $category))->assertSessionHas('success');
    }

    public function test_admin_dashboard_renders_cards(): void
    {
        $this->activeTask();
        $this->actingAs($this->admin())->get('/admin')->assertOk()->assertSee('Platform revenue')->assertSee('Tasks completed over time');
    }
}
