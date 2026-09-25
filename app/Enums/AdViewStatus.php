<?php

namespace App\Enums;

enum AdViewStatus: string
{
    // Ad requested, not finished yet
    case Started = 'started';
    // Watched in full; waits for the ad network to pay the platform
    case Counted = 'counted';
    // Included in an ad payout; the developer's share is in their wallet
    case Paid = 'paid';
    // Watched a demo ad on a server that does not count demo views
    case Unpaid = 'unpaid';

    public function label(): string
    {
        return match ($this) {
            self::Counted => 'Awaiting payout',
            default => ucfirst($this->value),
        };
    }
}
