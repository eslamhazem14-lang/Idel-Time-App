<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Notifications\Messages\MailMessage;

class TaskApprovedNotification extends PlatformNotification
{
    protected bool $sendsMail = true;

    public function __construct(public readonly Task $task) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject("Your task is live: {$this->task->title}")
            ->markdown('mail.task-approved', ['task' => $this->task, 'user' => $notifiable]);
    }

    protected function title(): string
    {
        return 'Task approved';
    }

    protected function body(): string
    {
        return "“{$this->task->title}” is live and visible to developers.";
    }

    protected function url(): ?string
    {
        return route('requester.tasks.show', $this->task);
    }

    protected function icon(): string
    {
        return 'check';
    }
}
