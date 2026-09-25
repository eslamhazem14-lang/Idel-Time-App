<x-mail::message>
# Approved: +{{ $submission->reward->format() }}

Your submission for **{{ $submission->task->title }}** was approved{{ $submission->auto_approved ? ' automatically' : '' }}. The reward is now in your available balance.

<x-mail::button :url="route('developer.wallet')">Open wallet</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
