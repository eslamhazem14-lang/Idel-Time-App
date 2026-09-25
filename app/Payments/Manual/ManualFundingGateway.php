<?php

namespace App\Payments\Manual;

use App\Models\Deposit;
use App\Payments\Contracts\FundingGateway;
use App\Payments\GatewayResult;

/** The requester sends a bank transfer / PayPal payment; an admin confirms receipt. */
class ManualFundingGateway implements FundingGateway
{
    public function name(): string
    {
        return 'manual';
    }

    public function initiate(Deposit $deposit): GatewayResult
    {
        return new GatewayResult(
            requiresManualSettlement: true,
            reference: 'IDLE-D'.str_pad((string) $deposit->id, 6, '0', STR_PAD_LEFT),
            instructions: 'Send the payment and include reference IDLE-D'.str_pad((string) $deposit->id, 6, '0', STR_PAD_LEFT).'. Funds are credited once an administrator confirms receipt.',
        );
    }
}
