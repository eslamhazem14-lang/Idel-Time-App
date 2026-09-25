<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\WalletBucket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

/**
 * Immutable ledger entry. Once written it can never be updated or deleted;
 * corrections are made with new, offsetting entries.
 */
class WalletTransaction extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount' => MoneyCast::class,
            'balance_after' => MoneyCast::class,
            'type' => TransactionType::class,
            'bucket' => WalletBucket::class,
            'status' => TransactionStatus::class,
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Wallet transactions are immutable.'));
        static::deleting(fn () => throw new LogicException('Wallet transactions are immutable.'));
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
