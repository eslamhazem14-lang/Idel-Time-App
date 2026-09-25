<x-mail::message>
# Your task is live

**{{ $task->title }}** was approved and is now visible to developers.

<x-mail::table>
| | |
|:--|--:|
| Reward per completion | {{ $task->reward->format() }} |
| Workers | {{ $task->available_slots }} |
| Budget reserved | {{ $task->total_budget->format() }} |
</x-mail::table>

<x-mail::button :url="route('requester.tasks.show', $task)">View task</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
