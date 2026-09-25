<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Notifications\Messages\MailMessage;

class TaskRejectedNotification extends PlatformNotification
{
    protected bool $sendsMail = true;

    public function __construct(public readonly Task $task) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject("Task not approved: {$this->task->title}")
            ->markdown('mail.task-rejected', ['task' => $this->task, 'user' => $notifiable]);
    }

    protected function title(): string
    {
        return 'Task not approved';
    }

    protected function body(): string
    {
        return "“{$this->task->title}” was not approved: {$this->task->rejection_reason}. The budget was returned to your balance.";
    }

    protected function url(): ?string
    {
        return route('requester.tasks.show', $this->task);
    }

    protected function icon(): string
    {
        return 'x';
    }
}
