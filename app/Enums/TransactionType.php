<?php

namespace App\Enums;

enum TransactionType: string
{
    case TaskReward = 'task_reward';
    case Withdrawal = 'withdrawal';
    case Refund = 'refund';
    case PlatformFee = 'platform_fee';
    case Adjustment = 'adjustment';
    case Deposit = 'deposit';
    case TaskEscrow = 'task_escrow';
    case TaskPayment = 'task_payment';

    public function label(): string
    {
        return match ($this) {
            self::TaskReward => 'Task reward',
            self::PlatformFee => 'Platform fee',
            self::TaskEscrow => 'Task budget reserved',
            self::TaskPayment => 'Task payment',
            default => ucfirst($this->value),
        };
    }
}
