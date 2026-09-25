<x-layouts.app title="Submissions">
    <x-page-header title="Submissions" />
    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('admin.submissions.index') }}" class="btn-sm {{ empty($filters) ? 'btn-primary' : 'btn-secondary' }}">All</a>
        @foreach (\App\Enums\SubmissionStatus::cases() as $s)
            <a href="{{ route('admin.submissions.index', ['status' => $s->value]) }}" class="btn-sm {{ ($filters['status'] ?? '') === $s->value ? 'btn-primary' : 'btn-secondary' }}">{{ $s->label() }}</a>
        @endforeach
        <a href="{{ route('admin.submissions.index', ['flagged' => 1]) }}" class="btn-sm {{ ($filters['flagged'] ?? false) ? 'btn-primary' : 'btn-secondary' }}"><x-icon name="shield" class="size-3.5" /> Flagged</a>
    </div>
    <div class="card overflow-x-auto">
        @if ($submissions->isEmpty())
            <x-empty icon="inbox" title="No submissions" />
        @else
            <table class="table">
                <thead><tr><th>#</th><th>Task</th><th>Developer</th><th>Requester</th><th>Submitted</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach ($submissions as $s)
                        <tr>
                            <td><a href="{{ route('admin.submissions.show', $s) }}" class="font-mono text-xs hover:text-primary">#{{ $s->id }}</a></td>
                            <td class="max-w-xs truncate"><a href="{{ route('admin.submissions.show', $s) }}" class="hover:text-primary">{{ $s->task->title }}</a></td>
                            <td>{{ $s->developer->name }}</td>
                            <td class="text-muted">{{ $s->task->requester->name }}</td>
                            <td class="text-muted">{{ $s->submitted_at->diffForHumans() }}</td>
                            <td><x-status :value="$s->status" /> @if ($s->is_flagged)<x-badge color="red">Flagged</x-badge>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4">{{ $submissions->links() }}</div>
        @endif
    </div>
</x-layouts.app>
