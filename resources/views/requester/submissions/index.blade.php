<x-layouts.app title="Review queue">
    <x-page-header title="Review queue" subtitle="Approve good work quickly — developers are paid on approval." />
    <div class="mb-4 flex flex-wrap gap-2">
        @foreach (\App\Enums\SubmissionStatus::cases() as $s)
            <a href="{{ route('requester.submissions.index', ['status' => $s->value]) }}" class="btn-sm {{ $status === $s->value ? 'btn-primary' : 'btn-secondary' }}">{{ $s->label() }}</a>
        @endforeach
    </div>
    <div class="card overflow-x-auto">
        @if ($submissions->isEmpty())
            <x-empty icon="inbox" title="No submissions" text="Nothing in this state right now." />
        @else
            <table class="table">
                <thead><tr><th>Task</th><th>Developer</th><th>Submitted</th><th>Time</th><th>Signals</th><th></th></tr></thead>
                <tbody>
                    @foreach ($submissions as $s)
                        <tr>
                            <td class="max-w-xs truncate">{{ $s->task->title }}</td>
                            <td>{{ $s->developer->name }}<div class="text-xs text-faint">{{ $s->developer->developerProfile?->approval_rate ? $s->developer->developerProfile->approval_rate.'% approval' : 'New developer' }}</div></td>
                            <td class="text-muted">{{ $s->submitted_at->diffForHumans() }}</td>
                            <td class="mono-num text-muted">{{ $s->timeSpentForHumans() }}</td>
                            <td>@if ($s->is_flagged)<x-badge color="red"><x-icon name="shield" class="size-3" /> Flagged</x-badge>@else<span class="text-faint">—</span>@endif</td>
                            <td class="text-right"><a href="{{ route('requester.submissions.show', $s) }}" class="btn-secondary btn-sm">{{ $s->isPending() ? 'Review' : 'View' }}</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4">{{ $submissions->links() }}</div>
        @endif
    </div>
</x-layouts.app>
