<?php

namespace App\Notifications;

use App\Enums\DepositStatus;
use App\Models\Deposit;

class DepositProcessedNotification extends PlatformNotification
{
    public function __construct(public readonly Deposit $deposit) {}

    protected function title(): string
    {
        return $this->deposit->status === DepositStatus::Completed ? 'Funds added' : 'Deposit rejected';
    }

    protected function body(): string
    {
        return $this->deposit->status === DepositStatus::Completed ? "{$this->deposit->amount->format()} was added to your balance." : "Your {$this->deposit->amount->format()} deposit could not be confirmed: {$this->deposit->admin_note}";
    }

    protected function url(): ?string
    {
        return route('requester.billing');
    }

    protected function icon(): string
    {
        return 'wallet';
    }
}
