<?php

namespace App\Enums;

enum FraudFlagStatus: string
{
    case Open = 'open';
    case Confirmed = 'confirmed';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'amber',
            self::Confirmed => 'red',
            self::Dismissed => 'gray',
        };
    }
}
