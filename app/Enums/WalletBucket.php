<?php

namespace App\Enums;

/**
 * Every wallet has two ledgers: "available" (spendable / withdrawable) and
 * "pending" (developer: rewards awaiting review; requester: budget held in escrow).
 */
enum WalletBucket: string
{
    case Available = 'available';
    case Pending = 'pending';

    public function column(): string
    {
        return $this === self::Available ? 'balance' : 'pending_balance';
    }
}
