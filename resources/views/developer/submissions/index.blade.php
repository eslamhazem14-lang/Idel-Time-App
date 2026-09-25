<x-layouts.app title="Submissions">
    <x-page-header title="Submissions" subtitle="Everything you've submitted and where it stands." />
    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('developer.submissions.index') }}" class="btn-sm {{ ! $status ? 'btn-primary' : 'btn-secondary' }}">All</a>
        @foreach (\App\Enums\SubmissionStatus::cases() as $s)
            <a href="{{ route('developer.submissions.index', ['status' => $s->value]) }}" class="btn-sm {{ $status === $s->value ? 'btn-primary' : 'btn-secondary' }}">{{ $s->label() }}</a>
        @endforeach
    </div>
    <div class="card overflow-x-auto">
        @if ($submissions->isEmpty())
            <x-empty icon="list" title="No submissions" text="When you submit a task, you'll track its review here.">
                <a href="{{ route('developer.tasks.index') }}" class="btn-primary">Find a task</a>
            </x-empty>
        @else
            <table class="table">
                <thead><tr><th>Task</th><th>Submitted</th><th>Time spent</th><th>Reward</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach ($submissions as $s)
                        <tr class="cursor-pointer" onclick="location.href='{{ route('developer.submissions.show', $s) }}'">
                            <td><a href="{{ route('developer.submissions.show', $s) }}" class="font-medium hover:text-primary">{{ $s->task->title }}</a><div class="text-xs text-faint">{{ $s->task->category->name }}</div></td>
                            <td class="text-muted">{{ $s->submitted_at->diffForHumans() }}</td>
                            <td class="text-muted mono-num">{{ $s->timeSpentForHumans() }}</td>
                            <td class="mono-num {{ $s->status->value === 'approved' ? 'text-secondary' : '' }}">{{ $s->reward->format() }}</td>
                            <td><x-status :value="$s->status" />@if ($s->dispute) <x-badge color="violet">Appealed</x-badge>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4">{{ $submissions->links() }}</div>
        @endif
    </div>
</x-layouts.app>
