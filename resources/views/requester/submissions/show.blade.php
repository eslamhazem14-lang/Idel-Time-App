<x-layouts.app :title="'Review submission #'.$submission->id">
    <x-page-header :title="'Submission #'.$submission->id" :subtitle="$task->title" :back="route('requester.tasks.show', $task)">
        <x-status :value="$submission->status" />
        @if ($next)<a href="{{ route('requester.submissions.show', $next) }}" class="btn-ghost">Next <x-icon name="chevron-right" /></a>@endif
    </x-page-header>
    @include('shared.submission-review', ['routePrefix' => 'requester.submissions', 'canReview' => $submission->isPending(), 'history' => $history])
</x-layouts.app>
