<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Balances are cached aggregates of the wallet_transactions ledger and must
 * only be changed through App\Services\WalletService.
 *
 * @property Money $balance
 * @property Money $pending_balance
 * @property Money $lifetime_earnings
 * @property Money $lifetime_spending
 */
class Wallet extends Model
{
    public const TYPE_USER = 'user';

    public const TYPE_PLATFORM = 'platform';

    protected $guarded = ['id', 'balance', 'pending_balance', 'lifetime_earnings', 'lifetime_spending'];

    protected function casts(): array
    {
        return [
            'balance' => MoneyCast::class,
            'pending_balance' => MoneyCast::class,
            'lifetime_earnings' => MoneyCast::class,
            'lifetime_spending' => MoneyCast::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
