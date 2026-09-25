<?php

namespace App\Enums;

enum BatchStatus: string
{
    case PendingApproval = 'pending_approval';
    case Active = 'active';
    case Rejected = 'rejected';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return $this === self::PendingApproval ? 'Pending approval' : ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::PendingApproval => 'amber',
            self::Rejected => 'red',
            self::Completed => 'violet',
            self::Cancelled => 'gray',
        };
    }
}
