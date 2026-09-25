<?php

namespace App\Payments\Contracts;

use App\Models\Withdrawal;
use App\Payments\GatewayResult;

/**
 * Sends money from the platform to a developer. Implementations must be
 * idempotent per withdrawal id.
 */
interface PayoutGateway
{
    public function name(): string;

    /** Called when an admin starts processing a withdrawal. */
    public function initiate(Withdrawal $withdrawal): GatewayResult;
}
