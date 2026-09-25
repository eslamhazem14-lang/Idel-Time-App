<x-mail::message>
# Submission rejected

Your submission for **{{ $submission->task->title }}** was rejected.

**Reason:** {{ $submission->rejection_reason }}

If you believe this decision is wrong, you can appeal within 14 days from the submission page.

<x-mail::button :url="route('developer.submissions.show', $submission)">View submission</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
