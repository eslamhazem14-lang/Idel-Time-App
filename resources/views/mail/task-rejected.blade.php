<x-mail::message>
# Task not approved

**{{ $task->title }}** was not approved by our moderation team.

**Reason:** {{ $task->rejection_reason }}

The reserved budget has been returned to your available balance. You can edit the task and submit it again.

<x-mail::button :url="route('requester.tasks.show', $task)">Review task</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
