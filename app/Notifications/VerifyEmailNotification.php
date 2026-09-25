<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)->subject('Verify your email address')
            ->markdown('mail.verify-email', ['url' => $this->verificationUrl($notifiable), 'user' => $notifiable]);
    }
}
