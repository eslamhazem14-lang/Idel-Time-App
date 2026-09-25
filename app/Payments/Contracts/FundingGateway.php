<?php

namespace App\Payments\Contracts;

use App\Models\Deposit;
use App\Payments\GatewayResult;

/**
 * Brings requester money into the platform (card checkout, invoice, bank transfer...).
 */
interface FundingGateway
{
    public function name(): string;

    public function initiate(Deposit $deposit): GatewayResult;
}
