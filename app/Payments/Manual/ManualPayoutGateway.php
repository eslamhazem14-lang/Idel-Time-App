<?php

namespace App\Payments\Manual;

use App\Models\Withdrawal;
use App\Payments\Contracts\PayoutGateway;
use App\Payments\GatewayResult;

/** An administrator pays the developer outside the platform, then marks the withdrawal paid. */
class ManualPayoutGateway implements PayoutGateway
{
    public function name(): string
    {
        return 'manual';
    }

    public function initiate(Withdrawal $withdrawal): GatewayResult
    {
        return new GatewayResult(requiresManualSettlement: true, reference: 'MANUAL-W'.$withdrawal->id);
    }
}
