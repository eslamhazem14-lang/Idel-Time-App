<?php

namespace App\Payments;

use App\Payments\Contracts\FundingGateway;
use App\Payments\Contracts\PayoutGateway;
use InvalidArgumentException;

class PaymentGatewayManager
{
    public function payout(?string $driver = null): PayoutGateway
    {
        return $this->resolve($driver ?? config('payments.payout_driver'), 'payout');
    }

    public function funding(?string $driver = null): FundingGateway
    {
        return $this->resolve($driver ?? config('payments.funding_driver'), 'funding');
    }

    private function resolve(string $driver, string $kind): object
    {
        $class = config("payments.drivers.{$driver}.{$kind}")
            ?? throw new InvalidArgumentException("Payment driver [{$driver}] has no {$kind} gateway.");

        return app($class);
    }
}
