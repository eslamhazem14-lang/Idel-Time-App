<?php

namespace App\Enums;

enum DepositStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Completed => 'green',
            self::Rejected => 'red',
        };
    }
}
