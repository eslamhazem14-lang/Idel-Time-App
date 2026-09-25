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
    case AdReward = 'ad_reward';
    case AdRevenue = 'ad_revenue';

    public function label(): string
    {
        return match ($this) {
            self::TaskReward => 'Task reward',
            self::PlatformFee => 'Platform fee',
            self::TaskEscrow => 'Task budget reserved',
            self::TaskPayment => 'Task payment',
            self::AdReward => 'Ad revenue share',
            self::AdRevenue => 'Ad network payout',
            default => ucfirst($this->value),
        };
    }
}
