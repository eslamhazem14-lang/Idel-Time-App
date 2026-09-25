<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case BankTransfer = 'bank_transfer';
    case Paypal = 'paypal';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::BankTransfer => 'Bank transfer (manual)',
            self::Paypal => 'PayPal',
            self::Other => 'Other / manual',
        };
    }

    public function accountHint(): string
    {
        return match ($this) {
            self::BankTransfer => 'Account holder, bank name, IBAN / account number, SWIFT',
            self::Paypal => 'PayPal email address',
            self::Other => 'Describe how you would like to be paid',
        };
    }
}
