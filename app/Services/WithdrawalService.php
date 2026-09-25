<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\TransactionType;
use App\Enums\WalletBucket;
use App\Enums\WithdrawalStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\User;
use App\Models\Withdrawal;
use App\Notifications\AdminAlertNotification;
use App\Notifications\WithdrawalProcessedNotification;
use App\Notifications\WithdrawalSubmittedNotification;
use App\Payments\PaymentGatewayManager;
use App\Support\AdminNotifier;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

/**
 * Withdrawals debit the developer's available balance immediately (funds are
 * held by the platform) and are settled by an admin via the payout gateway.
 * A rejected withdrawal is refunded with an offsetting ledger entry.
 */
class WithdrawalService
{
    public function __construct(
        private readonly WalletService $wallets,
        private readonly SettingsService $settings,
        private readonly PaymentGatewayManager $gateways,
        private readonly ActivityLogger $logger,
    ) {}

    public function enabledMethods(): array
    {
        return array_values(array_filter(
            PaymentMethod::cases(),
            fn (PaymentMethod $m) => in_array($m->value, (array) $this->settings->get('withdrawal_methods', []), true),
        ));
    }

    public function request(User $developer, Money $amount, PaymentMethod $method, string $accountDetails): Withdrawal
    {
        if (! $developer->isDeveloper()) {
            throw BusinessRuleException::make('Only developers can withdraw earnings.', 'forbidden', 403);
        }
        if ($developer->isSuspended()) {
            throw BusinessRuleException::make('Your account is suspended.', 'suspended', 403);
        }
        if ($this->settings->get('require_email_verification') && ! $developer->hasVerifiedEmail()) {
            throw BusinessRuleException::make('Please verify your email address before withdrawing.', 'unverified', 403);
        }
        $minimum = $this->settings->money('min_withdrawal');
        if ($amount->lessThan($minimum)) {
            throw BusinessRuleException::make("The minimum withdrawal is {$minimum->format()}.");
        }
        if (! in_array($method, $this->enabledMethods(), true)) {
            throw BusinessRuleException::make('This withdrawal method is not available.');
        }

        $withdrawal = DB::transaction(function () use ($developer, $amount, $method, $accountDetails) {
            $withdrawal = Withdrawal::query()->create([
                'developer_id' => $developer->id,
                'amount' => $amount,
                'method' => $method,
                'account_details' => $accountDetails,
                'status' => WithdrawalStatus::Pending,
                'gateway' => $this->gateways->payout()->name(),
            ]);

            // Throws InsufficientFundsException (rolling back the row above) if the balance is too low.
            $this->wallets->post($this->wallets->walletFor($developer), TransactionType::Withdrawal, WalletBucket::Available,
                $amount->negate(), "Withdrawal #{$withdrawal->id} via {$method->label()}", $withdrawal, actor: $developer);

            return $withdrawal;
        });

        $this->logger->log('withdrawal.requested', $withdrawal, ['amount' => $amount->toDecimal()], $developer);
        $developer->notify(new WithdrawalSubmittedNotification($withdrawal));
        AdminNotifier::send(new AdminAlertNotification(
            'New withdrawal request', "{$developer->name} requested {$amount->format()} via {$method->label()}",
            route('admin.withdrawals.index'), 'wallet',
        ));

        return $withdrawal;
    }

    public function markProcessing(Withdrawal $withdrawal, User $admin, ?string $note = null): Withdrawal
    {
        return $this->transition($withdrawal, [WithdrawalStatus::Pending], WithdrawalStatus::Processing, $admin, $note, function (Withdrawal $w) {
            $result = $this->gateways->payout($w->gateway)->initiate($w);
            $w->gateway_reference = $result->reference;
        });
    }

    public function markPaid(Withdrawal $withdrawal, User $admin, ?string $note = null, ?string $reference = null): Withdrawal
    {
        $w = $this->transition($withdrawal, [WithdrawalStatus::Pending, WithdrawalStatus::Processing], WithdrawalStatus::Paid, $admin, $note,
            function (Withdrawal $w) use ($reference) {
                if ($reference) {
                    $w->gateway_reference = $reference;
                }
            });
        $w->developer->notify(new WithdrawalProcessedNotification($w));

        return $w;
    }

    public function reject(Withdrawal $withdrawal, User $admin, string $note): Withdrawal
    {
        $w = $this->transition($withdrawal, [WithdrawalStatus::Pending, WithdrawalStatus::Processing], WithdrawalStatus::Rejected, $admin, $note,
            function (Withdrawal $w) use ($admin) {
                $this->wallets->post($this->wallets->walletFor($w->developer), TransactionType::Refund, WalletBucket::Available,
                    $w->amount, "Withdrawal #{$w->id} rejected — funds returned", $w, actor: $admin);
            });
        $w->developer->notify(new WithdrawalProcessedNotification($w));

        return $w;
    }

    private function transition(Withdrawal $withdrawal, array $from, WithdrawalStatus $to, User $admin, ?string $note, ?callable $effect = null): Withdrawal
    {
        DB::transaction(function () use ($withdrawal, $from, $to, $admin, $note, $effect) {
            $locked = Withdrawal::query()->lockForUpdate()->findOrFail($withdrawal->id);
            if (! in_array($locked->status, $from, true)) {
                throw BusinessRuleException::make("A {$locked->status->label()} withdrawal cannot be marked {$to->label()}.");
            }
            $locked->status = $to;
            $locked->admin_note = $note ?: $locked->admin_note;
            $locked->processed_by = $admin->id;
            if (in_array($to, [WithdrawalStatus::Paid, WithdrawalStatus::Rejected], true)) {
                $locked->processed_at = now();
            }
            if ($effect) {
                $effect($locked);
            }
            $locked->save();
        });

        $this->logger->log('withdrawal.'.$to->value, $withdrawal, ['note' => $note], $admin);

        return $withdrawal->refresh();
    }
}
