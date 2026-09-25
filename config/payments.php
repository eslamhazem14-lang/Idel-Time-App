<?php

use App\Payments\Manual\ManualFundingGateway;
use App\Payments\Manual\ManualPayoutGateway;

/*
|--------------------------------------------------------------------------
| Payment gateways
|--------------------------------------------------------------------------
|
| Money movement with the outside world is isolated behind gateway contracts
| (App\Payments\Contracts\PayoutGateway / FundingGateway). The MVP ships
| with a "manual" driver: an administrator settles the transfer outside the
| platform and marks it paid. Add Stripe / PayPal / Wise drivers by
| implementing the contract and registering the class below.
|
*/

return [
    'payout_driver' => env('PAYOUT_DRIVER', 'manual'),
    'funding_driver' => env('FUNDING_DRIVER', 'manual'),

    'drivers' => [
        'manual' => [
            'payout' => ManualPayoutGateway::class,
            'funding' => ManualFundingGateway::class,
        ],
    ],
];
