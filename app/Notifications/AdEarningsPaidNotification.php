<?php

namespace App\Notifications;

use App\Models\AdPayout;
use App\Support\Money;

class AdEarningsPaidNotification extends PlatformNotification
{
    public function __construct(public readonly AdPayout $payout, public readonly string $amount, public readonly int $views) {}

    protected function title(): string
    {
        return 'Ad earnings paid';
    }

    protected function body(): string
    {
        $period = $this->payout->period_start->format('M j').' – '.$this->payout->period_end->format('M j, Y');

        return Money::of($this->amount)->format()." for {$this->views} ads you watched ({$period}) is now in your available balance.";
    }

    protected function url(): ?string
    {
        return route('developer.wallet');
    }

    protected function icon(): string
    {
        return 'play';
    }
}
