<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\WalletBucket;
use App\Exceptions\InsufficientFundsException;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

/**
 * The only code path allowed to change a wallet balance. Every change is
 * paired with an immutable ledger row, inside a database transaction, with
 * the wallet row locked (SELECT ... FOR UPDATE).
 */
class WalletService
{
    public function walletFor(User $user): Wallet
    {
        $wallet = $user->wallet()->firstOrCreate([], ['type' => Wallet::TYPE_USER, 'currency' => config('platform.currency')]);

        return $wallet->wasRecentlyCreated ? $wallet->refresh() : $wallet;
    }

    public function platformWallet(): Wallet
    {
        $wallet = Wallet::query()->firstOrCreate(
            ['type' => Wallet::TYPE_PLATFORM, 'user_id' => null],
            ['currency' => config('platform.currency')],
        );

        return $wallet->wasRecentlyCreated ? $wallet->refresh() : $wallet;
    }

    /**
     * Post one signed ledger entry to a wallet bucket.
     *
     * @param  string|null  $lifetime  'lifetime_earnings' | 'lifetime_spending' counter to move by |amount|
     * @param  int  $lifetimeSign  +1 to increase the lifetime counter, -1 to reverse it (clawbacks)
     */
    public function post(
        Wallet $wallet,
        TransactionType $type,
        WalletBucket $bucket,
        Money $amount,
        string $description,
        ?Model $reference = null,
        TransactionStatus $status = TransactionStatus::Completed,
        array $meta = [],
        ?User $actor = null,
        ?string $lifetime = null,
        int $lifetimeSign = 1,
        bool $allowNegative = false,
    ): WalletTransaction {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Wallet operations must run inside a database transaction.');
        }
        if ($amount->isZero()) {
            throw new LogicException('Refusing to post a zero-amount ledger entry.');
        }

        /** @var Wallet $locked */
        $locked = Wallet::query()->whereKey($wallet->getKey())->lockForUpdate()->firstOrFail();

        $column = $bucket->column();
        $newBalance = $locked->{$column}->add($amount);
        if ($newBalance->isNegative() && ! $allowNegative) {
            throw new InsufficientFundsException;
        }

        $locked->{$column} = $newBalance;
        if ($lifetime !== null) {
            $locked->{$lifetime} = $locked->{$lifetime}->add($amount->abs()->multiply($lifetimeSign >= 0 ? 1 : -1));
        }
        $locked->save();

        // keep the caller's instance in sync
        $wallet->setRawAttributes($locked->getAttributes(), true);

        return $locked->transactions()->create([
            'uuid' => (string) Str::uuid(),
            'type' => $type,
            'bucket' => $bucket,
            'amount' => $amount,
            'balance_after' => $newBalance,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'description' => Str::limit($description, 250),
            'status' => $status,
            'meta' => $meta ?: null,
            'created_by' => $actor?->id,
        ]);
    }

    /** Move funds between the two buckets of the same wallet (e.g. escrow a task budget). */
    public function moveBetweenBuckets(Wallet $wallet, WalletBucket $from, Money $amount, TransactionType $type, string $description, ?Model $reference = null, ?User $actor = null): void
    {
        $to = $from === WalletBucket::Available ? WalletBucket::Pending : WalletBucket::Available;
        $this->post($wallet, $type, $from, $amount->negate(), $description, $reference, actor: $actor);
        $this->post($wallet, $type, $to, $amount, $description, $reference, actor: $actor);
    }

    /** Manual admin correction. Always creates an "adjustment" ledger row with the admin's note. */
    public function adjust(User $user, Money $amount, string $note, User $admin): WalletTransaction
    {
        return DB::transaction(function () use ($user, $amount, $note, $admin) {
            $tx = $this->post(
                $this->walletFor($user), TransactionType::Adjustment, WalletBucket::Available, $amount,
                'Adjustment: '.$note, null, TransactionStatus::Completed, ['note' => $note], $admin,
            );
            app(ActivityLogger::class)->log('wallet.adjusted', $user, ['amount' => $amount->toDecimal(), 'note' => $note], $admin);

            return $tx;
        });
    }

    /**
     * Verify cached balances equal the sum of their ledger rows.
     *
     * @return array<int, array{wallet_id:int, bucket:string, cached:string, ledger:string}>
     */
    public function reconcile(): array
    {
        $problems = [];
        $sums = WalletTransaction::query()
            ->selectRaw('wallet_id, bucket, SUM(amount) as total')
            ->groupBy('wallet_id', 'bucket')->get()
            ->groupBy('wallet_id');

        Wallet::query()->each(function (Wallet $wallet) use ($sums, &$problems) {
            foreach (WalletBucket::cases() as $bucket) {
                $row = $sums->get($wallet->id)?->firstWhere('bucket', $bucket);
                $ledger = Money::of((string) ($row->total ?? '0'));
                $cached = $wallet->{$bucket->column()};
                if (! $ledger->equals($cached)) {
                    $problems[] = ['wallet_id' => $wallet->id, 'bucket' => $bucket->value, 'cached' => $cached->toDecimal(), 'ledger' => $ledger->toDecimal()];
                }
            }
        });

        return $problems;
    }
}
