<?php

namespace App\DTOs;

use App\Support\Money;

/**
 * Server-computed price of a task. Client-supplied totals are never trusted.
 */
final class PriceQuote
{
    public function __construct(
        public readonly Money $reward,
        public readonly Money $platformFee,
        public readonly string $commissionPercent,
        public readonly int $slots,
    ) {}

    public function unitCost(): Money
    {
        return $this->reward->add($this->platformFee);
    }

    public function total(): Money
    {
        return $this->unitCost()->multiply($this->slots);
    }

    public function toArray(): array
    {
        return [
            'reward' => $this->reward->toDecimal(),
            'platform_fee' => $this->platformFee->toDecimal(),
            'commission_percent' => $this->commissionPercent,
            'unit_cost' => $this->unitCost()->toDecimal(),
            'slots' => $this->slots,
            'total' => $this->total()->toDecimal(),
        ];
    }
}
