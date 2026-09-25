<?php

namespace App\Enums;

enum ClaimStatus: string
{
    case Active = 'active';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'violet',
            self::Submitted => 'amber',
            self::Approved => 'green',
            self::Rejected => 'red',
            self::Expired, self::Cancelled => 'gray',
        };
    }

    /** Claims in these states occupy a slot of the task. */
    public static function reserving(): array
    {
        return [self::Active->value, self::Submitted->value];
    }
}
