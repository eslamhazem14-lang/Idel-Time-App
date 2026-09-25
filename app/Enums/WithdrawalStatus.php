<?php

namespace App\Enums;

enum WithdrawalStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Paid = 'paid';
    case Rejected = 'rejected';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Processing => 'violet',
            self::Paid => 'green',
            self::Rejected => 'red',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::Processing], true);
    }
}
