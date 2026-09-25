<?php

namespace App\Http\Resources;

use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Wallet */
class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'currency' => $this->currency,
            'available_balance' => $this->balance->toDecimal(),
            'pending_balance' => $this->pending_balance->toDecimal(),
            'lifetime_earnings' => $this->lifetime_earnings->toDecimal(),
            'lifetime_spending' => $this->lifetime_spending->toDecimal(),
            'minimum_withdrawal' => settings()->money('min_withdrawal')->toDecimal(),
        ];
    }
}
