<?php

namespace App\Notifications;

use App\Models\Withdrawal;
use Illuminate\Notifications\Messages\MailMessage;

class WithdrawalSubmittedNotification extends PlatformNotification
{
    protected bool $sendsMail = true;

    public function __construct(public readonly Withdrawal $withdrawal) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject("Withdrawal request received ({$this->withdrawal->amount->format()})")
            ->markdown('mail.withdrawal-submitted', ['withdrawal' => $this->withdrawal, 'user' => $notifiable]);
    }

    protected function title(): string
    {
        return 'Withdrawal requested';
    }

    protected function body(): string
    {
        return "We received your {$this->withdrawal->amount->format()} withdrawal request via {$this->withdrawal->method->label()}.";
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
