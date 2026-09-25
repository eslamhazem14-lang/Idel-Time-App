<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A request that is valid in shape but violates a business rule
 * ("this task was just claimed by another developer"). The message is
 * safe to show to end users.
 */
class BusinessRuleException extends RuntimeException
{
    public function __construct(string $message, public readonly string $errorCode = 'business_rule', public readonly int $status = 422)
    {
        parent::__construct($message);
    }

    public static function make(string $message, string $code = 'business_rule', int $status = 422): static
    {
        return new static($message, $code, $status);
    }
}
