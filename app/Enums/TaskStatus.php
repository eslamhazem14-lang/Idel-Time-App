<?php

namespace App\Enums;

enum TaskStatus: string
{
    case PendingApproval = 'pending_approval';
    case Active = 'active';
    case Paused = 'paused';
    case Rejected = 'rejected';
    case Suspended = 'suspended';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PendingApproval => 'Pending approval',
            default => ucfirst($this->value),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::PendingApproval, self::Paused => 'amber',
            self::Rejected, self::Suspended => 'red',
            self::Completed => 'violet',
            self::Cancelled, self::Expired => 'gray',
        };
    }

    /** A closed task will never accept new claims again; unused escrow is refundable. */
    public function isClosed(): bool
    {
        return in_array($this, [self::Rejected, self::Completed, self::Cancelled, self::Expired], true);
    }
}
