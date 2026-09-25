<x-layouts.app :title="'Submission #'.$submission->id">
    <x-page-header :title="'Submission #'.$submission->id" :subtitle="$task->title.' · requester '.$task->requester->name" :back="route('admin.submissions.index')">
        <x-status :value="$submission->status" />
        <a href="{{ route('admin.tasks.show', $task) }}" class="btn-secondary">Open task</a>
    </x-page-header>

    @include('shared.submission-review', ['routePrefix' => 'admin.submissions', 'canReview' => $submission->isPending(), 'history' => $history, 'admin' => true])

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        @if (in_array($submission->status, [\App\Enums\SubmissionStatus::Approved, \App\Enums\SubmissionStatus::Rejected]))
            <div class="card card-pad">
                <h2 class="text-sm font-semibold">Override decision</h2>
                <p class="mt-1 text-xs text-muted">
                    @if ($submission->status === \App\Enums\SubmissionStatus::Rejected)
                        Approving pays the developer. If the task has no free slot, one extra slot is funded from the requester's balance.
                    @else
                        Reversing claws back the reward from the developer's available balance, reverses the commission and refunds the requester.
                    @endif
                </p>
                <form method="POST" action="{{ route('admin.submissions.override', $submission) }}" class="mt-4 space-y-3">
                    @csrf
                    <input type="hidden" name="to" value="{{ $submission->status === \App\Enums\SubmissionStatus::Rejected ? 'approved' : 'rejected' }}">
                    <x-textarea name="note" label="Reason (logged and shown to the developer)" rows="2" required />
                    <button class="{{ $submission->status === \App\Enums\SubmissionStatus::Rejected ? 'btn-success' : 'btn-danger' }}">
                        {{ $submission->status === \App\Enums\SubmissionStatus::Rejected ? 'Override: approve & pay' : 'Override: reverse approval' }}
                    </button>
                </form>
            </div>
        @endif
        @if ($duplicates->isNotEmpty())
            <div class="card">
                <h2 class="border-b border-line px-5 py-3 text-sm font-semibold">Identical answers elsewhere ({{ $duplicates->count() }})</h2>
                @foreach ($duplicates as $d)
                    <a href="{{ route('admin.submissions.show', $d) }}" class="flex justify-between border-b border-line/60 px-5 py-2.5 text-sm last:border-0 hover:bg-surface-2/60">
                        <span>#{{ $d->id }} · {{ $d->developer->name }}</span><x-status :value="$d->status" />
                    </a>
                @endforeach
            </div>
        @endif
        @if ($submission->dispute)
            <div class="card card-pad text-sm">
                <h2 class="font-semibold">Appeal</h2>
                <p class="mt-2 text-ink-2">{{ $submission->dispute->reason }}</p>
                <a href="{{ route('admin.disputes.show', $submission->dispute) }}" class="btn-secondary btn-sm mt-3">Open dispute</a>
            </div>
        @endif
    </div>
</x-layouts.app>
