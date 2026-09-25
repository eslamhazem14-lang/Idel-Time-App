<?php

namespace App\Exceptions;

class InsufficientFundsException extends BusinessRuleException
{
    public function __construct(string $message = 'Insufficient balance for this operation.')
    {
        parent::__construct($message, 'insufficient_funds');
    }
}
