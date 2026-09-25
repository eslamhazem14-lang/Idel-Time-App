<?php

namespace Tests\Feature;

use App\Enums\AdViewStatus;
use App\Enums\TransactionType;
use App\Models\AdView;
use App\Notifications\AdEarningsPaidNotification;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['platform.ads.provider' => 'demo', 'platform.ads.pay_demo_views' => true]);
    }

    private function watchAd($developer): AdView
    {
        $uuid = $this->actingAs($developer)->postJson(route('developer.watch.start'))->assertCreated()->json('view');
        $this->travel(20)->seconds();
        $this->postJson(route('developer.watch.complete', $uuid))->assertOk()->assertJsonPath('status', 'counted');
        $this->travel(40)->seconds(); // past the cooldown

        return AdView::query()->where('uuid', $uuid)->firstOrFail();
    }

    public function test_watching_an_ad_counts_it_without_paying_yet(): void
    {
        $dev = $this->developer();
        $this->actingAs($dev)->get(route('developer.watch'))->assertOk()->assertSee('Watch an ad');

        $view = $this->watchAd($dev);

        $this->assertSame(AdViewStatus::Counted, $view->status);
        $this->assertTrue($this->wallet($dev)->balance->isZero());
        $this->getJson(route('developer.watch.status'))->assertJsonPath('awaiting', 1)->assertJsonPath('today', 1);
    }

    public function test_ad_must_be_watched_for_the_minimum_time_and_only_once(): void
    {
        $dev = $this->developer();
        $uuid = $this->actingAs($dev)->postJson(route('developer.watch.start'))->json('view');

        $this->postJson(route('developer.watch.complete', $uuid))->assertUnprocessable()->assertJsonPath('code', 'ad_too_short');
        $this->travel(20)->seconds();
        $this->postJson(route('developer.watch.complete', $uuid))->assertOk();
        $this->postJson(route('developer.watch.complete', $uuid))->assertUnprocessable()->assertJsonPath('code', 'ad_already_completed');
    }

    public function test_cooldown_daily_cap_and_ownership(): void
    {
        settings()->setMany(['ad_daily_cap' => 2]);
        $dev = $this->developer();

        $this->actingAs($dev)->postJson(route('developer.watch.start'))->assertCreated();
        $this->postJson(route('developer.watch.start'))->assertStatus(429)->assertJsonPath('code', 'ad_cooldown');
        AdView::query()->delete();

        $this->watchAd($dev);
        $this->watchAd($dev);
        $this->postJson(route('developer.watch.start'))->assertUnprocessable()->assertJsonPath('code', 'ad_daily_cap');

        $other = $this->developer();
        $uuid = $this->actingAs($other)->postJson(route('developer.watch.start'))->json('view');
        $this->travel(20)->seconds();
        $this->actingAs($dev)->postJson(route('developer.watch.complete', $uuid))->assertForbidden();
    }

    public function test_demo_views_are_not_counted_when_demo_payouts_are_off(): void
    {
        config(['platform.ads.pay_demo_views' => false]);
        $dev = $this->developer();
        $uuid = $this->actingAs($dev)->postJson(route('developer.watch.start'))->json('view');
        $this->travel(20)->seconds();
        $this->postJson(route('developer.watch.complete', $uuid))->assertOk()->assertJsonPath('status', 'unpaid');
    }

    public function test_admin_payout_is_split_by_views_and_becomes_withdrawable(): void
    {
        Notification::fake();
        settings()->setMany(['ad_revenue_share_percent' => '50.00']);
        [$a, $b] = [$this->developer(), $this->developer()];
        $this->watchAd($a);
        $this->watchAd($a);
        $this->watchAd($a);
        $this->watchAd($b);

        $admin = $this->admin();
        $params = ['period_start' => now()->subDay()->toDateString(), 'period_end' => now()->toDateString(), 'gross_amount' => '10.00'];
        $this->actingAs($admin)->get(route('admin.ads.index', $params))->assertOk()->assertSee('$5.00')->assertSee('$3.75')->assertSee('$1.25');

        $this->post(route('admin.ads.distribute'), $params + ['reference' => 'GOOG-1'])->assertRedirect(route('admin.ads.index'));

        $this->assertSame('3.75', $this->wallet($a)->balance->toDecimal());
        $this->assertSame('1.25', $this->wallet($b)->balance->toDecimal());
        $this->assertSame('3.75', $this->wallet($a)->lifetime_earnings->toDecimal());
        $this->assertSame(0, AdView::query()->where('status', AdViewStatus::Counted)->count());
        $this->assertSame('5.00', app(WalletService::class)->platformWallet()->refresh()->balance->toDecimal());
        $this->assertDatabaseHas('wallet_transactions', ['type' => TransactionType::AdRevenue->value]);
        Notification::assertSentTo($a, AdEarningsPaidNotification::class);
        $this->assertLedgerReconciles();

        // The same views can't be paid twice
        $this->post(route('admin.ads.distribute'), $params)->assertSessionHas('error');
    }

    public function test_only_admins_can_distribute(): void
    {
        $this->actingAs($this->developer())->post(route('admin.ads.distribute'), [])->assertForbidden();
    }

    public function test_hook_token_and_idle_session_return_watch_url(): void
    {
        $dev = $this->developer();
        $this->actingAs($dev)->post(route('developer.connect.token'))->assertRedirect(route('developer.connect'))->assertSessionHas('hook_token');
        $this->get(route('developer.connect'))->assertOk()->assertSee('idletime-hook.sh');
        $this->assertSame(1, $dev->tokens()->count());

        Sanctum::actingAs($dev, ['idle:write']);
        $this->postJson('/api/idle-sessions', ['client' => 'cli', 'agent' => 'claude-code', 'expected_minutes' => 5])
            ->assertCreated()->assertJsonPath('watch_url', fn ($url) => str_contains($url, '/watch?idle='));
    }
}
