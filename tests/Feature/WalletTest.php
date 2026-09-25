<?php

namespace Tests\Feature;

use App\Enums\WithdrawalStatus;
use App\Models\Deposit;
use App\Models\Withdrawal;
use App\Notifications\WithdrawalProcessedNotification;
use App\Notifications\WithdrawalSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_page_shows_balances_and_empty_states(): void
    {
        $dev = $this->developer();
        $this->actingAs($dev)->get(route('developer.wallet'))->assertOk()->assertSee('No earnings yet')->assertSee('No withdrawals');
    }

    public function test_developer_requests_withdrawal(): void
    {
        Notification::fake();
        $dev = $this->developer();
        $this->fund($dev, '25.00');

        $this->actingAs($dev)->post(route('developer.withdrawals.store'), [
            'amount' => '20.00', 'method' => 'paypal', 'account_details' => 'dev@paypal.example',
        ])->assertRedirect()->assertSessionHas('success');

        $w = Withdrawal::query()->firstOrFail();
        $this->assertSame(WithdrawalStatus::Pending, $w->status);
        $this->assertSame('5.00', $this->wallet($dev)->balance->toDecimal());
        // payout details encrypted at rest
        $this->assertStringNotContainsString('dev@paypal.example', DB::table('withdrawals')->value('account_details'));
        $this->assertSame('dev@paypal.example', $w->account_details);
        Notification::assertSentTo($dev, WithdrawalSubmittedNotification::class);
        $this->assertLedgerReconciles();
    }

    public function test_withdrawal_rules(): void
    {
        $dev = $this->developer();
        $this->fund($dev, '15.00');

        $this->actingAs($dev)->post(route('developer.withdrawals.store'), ['amount' => '5.00', 'method' => 'paypal', 'account_details' => 'x@example.test'])
            ->assertSessionHas('error', 'The minimum withdrawal is $10.00.');
        $this->actingAs($dev)->post(route('developer.withdrawals.store'), ['amount' => '50.00', 'method' => 'paypal', 'account_details' => 'x@example.test'])
            ->assertSessionHas('error', 'Insufficient balance for this operation.');
        $this->actingAs($dev)->post(route('developer.withdrawals.store'), ['amount' => '10.00', 'method' => 'bitcoin', 'account_details' => 'x@example.test'])
            ->assertSessionHasErrors('method');
        $this->actingAs($dev)->post(route('developer.withdrawals.store'), ['amount' => '10.001', 'method' => 'paypal', 'account_details' => 'x@example.test'])
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('withdrawals', 0);
        $this->assertSame('15.00', $this->wallet($dev)->balance->toDecimal());
    }

    public function test_minimum_withdrawal_is_configurable(): void
    {
        settings()->set('min_withdrawal', '2.00');
        $dev = $this->developer();
        $this->fund($dev, '3.00');
        $this->actingAs($dev)->post(route('developer.withdrawals.store'), ['amount' => '2.00', 'method' => 'other', 'account_details' => 'Wise: dev@example.test'])
            ->assertSessionHas('success');
    }

    public function test_admin_marks_withdrawal_paid(): void
    {
        Notification::fake();
        $dev = $this->developer();
        $this->fund($dev, '30.00');
        $this->actingAs($dev)->post(route('developer.withdrawals.store'), ['amount' => '30.00', 'method' => 'bank_transfer', 'account_details' => 'IBAN XX00 TEST']);
        $w = Withdrawal::query()->firstOrFail();
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.withdrawals.show', $w))->assertOk()->assertSee('IBAN XX00 TEST');
        $this->actingAs($admin)->post(route('admin.withdrawals.update', $w), ['status' => 'processing'])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.withdrawals.update', $w), ['status' => 'paid', 'reference' => 'BANK-123'])->assertRedirect();

        $w->refresh();
        $this->assertSame(WithdrawalStatus::Paid, $w->status);
        $this->assertSame('BANK-123', $w->gateway_reference);
        $this->assertNotNull($w->processed_at);
        Notification::assertSentTo($dev, WithdrawalProcessedNotification::class);

        // cannot transition a paid withdrawal again
        $this->actingAs($admin)->post(route('admin.withdrawals.update', $w), ['status' => 'rejected', 'note' => 'x'])->assertSessionHas('error');
    }

    public function test_rejected_withdrawal_is_refunded(): void
    {
        $dev = $this->developer();
        $this->fund($dev, '12.00');
        $this->actingAs($dev)->post(route('developer.withdrawals.store'), ['amount' => '12.00', 'method' => 'paypal', 'account_details' => 'x@example.test']);
        $w = Withdrawal::query()->firstOrFail();

        $this->actingAs($this->admin())->post(route('admin.withdrawals.update', $w), ['status' => 'rejected'])->assertSessionHasErrors('note');
        $this->actingAs($this->admin())->post(route('admin.withdrawals.update', $w), ['status' => 'rejected', 'note' => 'PayPal account not found'])->assertRedirect();

        $this->assertSame(WithdrawalStatus::Rejected, $w->fresh()->status);
        $this->assertSame('12.00', $this->wallet($dev)->balance->toDecimal());
        $this->assertLedgerReconciles();
    }

    public function test_requester_deposit_is_credited_after_admin_confirmation(): void
    {
        $requester = $this->requester('0.00');
        $this->actingAs($requester)->post(route('requester.deposits.store'), ['amount' => '100.00', 'method' => 'bank_transfer'])->assertSessionHas('success');
        $deposit = Deposit::query()->firstOrFail();
        $this->assertSame('0.00', $this->wallet($requester)->balance->toDecimal());

        $this->actingAs($this->admin())->post(route('admin.deposits.update', $deposit), ['status' => 'completed'])->assertRedirect();
        $this->assertSame('100.00', $this->wallet($requester)->balance->toDecimal());
        $this->assertLedgerReconciles();
    }

    public function test_admin_wallet_adjustment_creates_ledger_entry(): void
    {
        $dev = $this->developer();
        $this->actingAs($this->admin())->post(route('admin.users.adjust-wallet', $dev), ['amount' => '-1.00', 'note' => 'Correction'])->assertSessionHas('error');
        $this->actingAs($this->admin())->post(route('admin.users.adjust-wallet', $dev), ['amount' => '3.50', 'note' => 'Goodwill credit'])->assertSessionHas('success');
        $this->assertSame('3.50', $this->wallet($dev)->balance->toDecimal());
        $this->assertDatabaseHas('wallet_transactions', ['type' => 'adjustment', 'description' => 'Adjustment: Goodwill credit']);
    }
}
