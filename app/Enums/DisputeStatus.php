<?php

namespace App\Enums;

enum DisputeStatus: string
{
    case Open = 'open';
    case Upheld = 'upheld';
    case Denied = 'denied';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Upheld => 'Appeal upheld',
            self::Denied => 'Appeal denied',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'amber',
            self::Upheld => 'green',
            self::Denied => 'red',
        };
    }
}
