<x-mail::message>
# Welcome, {{ $user->name }}

@if ($user->isDeveloper())
You're set up to earn during the minutes your AI coding agent is busy. Pick a task that fits, complete it, and get paid after approval.

<x-mail::button :url="route('developer.tasks.index')">Find a task</x-mail::button>
@else
You can now post microtasks that need human technical validation. Add funds, post a task, and review each submission before you pay.

<x-mail::button :url="route('requester.tasks.create')">Post your first task</x-mail::button>
@endif

Earnings depend on task availability, qualification, and approval.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
