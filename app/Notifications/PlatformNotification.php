<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * In-app (database) notification with an optional email. Queued: run a
 * queue worker in production, or use QUEUE_CONNECTION=sync locally.
 */
abstract class PlatformNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** Send an email in addition to the in-app notification. */
    protected bool $sendsMail = false;

    abstract protected function title(): string;

    abstract protected function body(): string;

    protected function url(): ?string
    {
        return null;
    }

    protected function icon(): string
    {
        return 'bell';
    }

    public function via(object $notifiable): array
    {
        return $this->sendsMail ? ['database', 'mail'] : ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'body' => $this->body(),
            'url' => $this->url(),
            'icon' => $this->icon(),
        ];
    }
}
