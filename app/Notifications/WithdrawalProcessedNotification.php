<?php

namespace App\Notifications;

use App\Enums\WithdrawalStatus;
use App\Models\Withdrawal;
use Illuminate\Notifications\Messages\MailMessage;

class WithdrawalProcessedNotification extends PlatformNotification
{
    protected bool $sendsMail = true;

    public function __construct(public readonly Withdrawal $withdrawal) {}

    public function toMail(object $notifiable): MailMessage
    {
        $paid = $this->withdrawal->status === WithdrawalStatus::Paid;

        return (new MailMessage)->subject($paid ? "Withdrawal paid ({$this->withdrawal->amount->format()})" : 'Withdrawal rejected')
            ->markdown('mail.withdrawal-processed', ['withdrawal' => $this->withdrawal, 'user' => $notifiable, 'paid' => $paid]);
    }

    protected function title(): string
    {
        return $this->withdrawal->status === WithdrawalStatus::Paid ? 'Withdrawal paid' : 'Withdrawal rejected';
    }

    protected function body(): string
    {
        return $this->withdrawal->status === WithdrawalStatus::Paid ? "{$this->withdrawal->amount->format()} was sent via {$this->withdrawal->method->label()}." : "Your {$this->withdrawal->amount->format()} withdrawal was rejected and the funds returned: {$this->withdrawal->admin_note}";
    }

    protected function url(): ?string
    {
        return route('developer.wallet');
    }

    protected function icon(): string
    {
        return 'wallet';
    }
}
