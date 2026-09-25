<?php

namespace App\Services;

use App\DTOs\PriceQuote;
use App\Support\Money;

class TaskPricingService
{
    public function __construct(private readonly SettingsService $settings) {}

    public function quote(Money|string $reward, int $slots = 1, ?string $commissionPercent = null): PriceQuote
    {
        $reward = Money::of($reward);
        $percent = $commissionPercent ?? $this->settings->commissionPercent();

        return new PriceQuote($reward, $reward->percentage($percent), $percent, max(1, $slots));
    }
}
