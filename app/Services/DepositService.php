<?php

namespace App\Services;

use App\Enums\DepositStatus;
use App\Enums\PaymentMethod;
use App\Enums\TransactionType;
use App\Enums\WalletBucket;
use App\Exceptions\BusinessRuleException;
use App\Models\Deposit;
use App\Models\User;
use App\Notifications\AdminAlertNotification;
use App\Notifications\DepositProcessedNotification;
use App\Payments\GatewayResult;
use App\Payments\PaymentGatewayManager;
use App\Support\AdminNotifier;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

/**
 * Requester funding. With the manual gateway, the requester sends money
 * off-platform and an admin confirms receipt, crediting the wallet.
 */
class DepositService
{
    public function __construct(
        private readonly WalletService $wallets,
        private readonly SettingsService $settings,
        private readonly PaymentGatewayManager $gateways,
        private readonly ActivityLogger $logger,
    ) {}

    /** @return array{0: Deposit, 1: GatewayResult} */
    public function request(User $requester, Money $amount, PaymentMethod $method, ?string $reference = null): array
    {
        if (! $requester->isRequester()) {
            throw BusinessRuleException::make('Only requesters can add funds.', 'forbidden', 403);
        }
        $minimum = $this->settings->money('min_deposit');
        if ($amount->lessThan($minimum)) {
            throw BusinessRuleException::make("The minimum deposit is {$minimum->format()}.");
        }

        $gateway = $this->gateways->funding();
        $deposit = Deposit::query()->create([
            'requester_id' => $requester->id,
            'amount' => $amount,
            'method' => $method,
            'reference' => $reference,
            'status' => DepositStatus::Pending,
            'gateway' => $gateway->name(),
        ]);
        $result = $gateway->initiate($deposit);
        $deposit->update(['gateway_reference' => $result->reference]);

        $this->logger->log('deposit.requested', $deposit, ['amount' => $amount->toDecimal()], $requester);
        AdminNotifier::send(new AdminAlertNotification(
            'New deposit to confirm', "{$requester->name} reported a {$amount->format()} payment ({$result->reference})",
            route('admin.deposits.index'), 'wallet',
        ));

        return [$deposit, $result];
    }

    public function approve(Deposit $deposit, User $admin, ?string $note = null): Deposit
    {
        DB::transaction(function () use ($deposit, $admin, $note) {
            $locked = $this->lockPending($deposit);
            $locked->forceFill(['status' => DepositStatus::Completed, 'processed_by' => $admin->id, 'processed_at' => now(), 'admin_note' => $note])->save();
            $this->wallets->post($this->wallets->walletFor($locked->requester), TransactionType::Deposit, WalletBucket::Available,
                $locked->amount, "Funds added ({$locked->gateway_reference})", $locked, actor: $admin);
        });

        $deposit->refresh();
        $this->logger->log('deposit.approved', $deposit, [], $admin);
        $deposit->requester->notify(new DepositProcessedNotification($deposit));

        return $deposit;
    }

    public function reject(Deposit $deposit, User $admin, string $note): Deposit
    {
        DB::transaction(function () use ($deposit, $admin, $note) {
            $this->lockPending($deposit)->forceFill([
                'status' => DepositStatus::Rejected, 'processed_by' => $admin->id, 'processed_at' => now(), 'admin_note' => $note,
            ])->save();
        });

        $deposit->refresh();
        $this->logger->log('deposit.rejected', $deposit, ['note' => $note], $admin);
        $deposit->requester->notify(new DepositProcessedNotification($deposit));

        return $deposit;
    }

    private function lockPending(Deposit $deposit): Deposit
    {
        $locked = Deposit::query()->lockForUpdate()->findOrFail($deposit->id);
        if ($locked->status !== DepositStatus::Pending) {
            throw BusinessRuleException::make('This deposit was already processed.');
        }

        return $locked;
    }
}
