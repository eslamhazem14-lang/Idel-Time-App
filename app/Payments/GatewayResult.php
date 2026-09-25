<?php

namespace App\Payments;

final class GatewayResult
{
    public function __construct(
        public readonly bool $requiresManualSettlement,
        public readonly ?string $reference = null,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $instructions = null,
    ) {}
}
