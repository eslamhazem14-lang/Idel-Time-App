@props(['submission'])
{{-- Reviewer view of a developer answer, rendered by the task type's partial --}}
@include($submission->task->handler()->view('review'), ['task' => $submission->task, 'answer' => $submission->answer])
