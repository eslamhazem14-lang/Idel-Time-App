<x-layouts.app title="My tasks">
    <x-page-header title="My tasks">
        <a href="{{ route('requester.batches.create') }}" class="btn-secondary"><x-icon name="layers" /> New batch</a>
        <a href="{{ route('requester.tasks.create') }}" class="btn-primary"><x-icon name="plus" /> Post a Task</a>
    </x-page-header>

    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('requester.tasks.index') }}" class="btn-sm {{ ! $status ? 'btn-primary' : 'btn-secondary' }}">All</a>
        @foreach (\App\Enums\TaskStatus::cases() as $s)
            <a href="{{ route('requester.tasks.index', ['status' => $s->value]) }}" class="btn-sm {{ $status === $s->value ? 'btn-primary' : 'btn-secondary' }}">{{ $s->label() }}</a>
        @endforeach
    </div>

    @if ($batches->isNotEmpty())
        <div class="mb-4 flex flex-wrap items-center gap-2 text-xs text-muted">
            Batches:
            @foreach ($batches as $b)
                <a href="{{ route('requester.batches.show', $b) }}" class="rounded-md border border-line px-2 py-1 text-ink-2 hover:border-line-strong">{{ $b->title }} <span class="text-faint">({{ $b->total_tasks }})</span></a>
            @endforeach
        </div>
    @endif

    <div class="card overflow-x-auto">
        @if ($tasks->isEmpty())
            <x-empty icon="layers" title="No tasks here" text="Tasks you post will show up here with their progress.">
                <a href="{{ route('requester.tasks.create') }}" class="btn-primary">Post a Task</a>
            </x-empty>
        @else
            <table class="table">
                <thead><tr><th>Task</th><th>Status</th><th>Progress</th><th>Reward</th><th>To review</th><th>Created</th></tr></thead>
                <tbody>
                    @foreach ($tasks as $task)
                        <tr>
                            <td><a href="{{ route('requester.tasks.show', $task) }}" class="font-medium hover:text-primary">{{ $task->title }}</a><div class="text-xs text-faint">{{ $task->category->name }}</div></td>
                            <td><x-status :value="$task->status" /></td>
                            <td class="min-w-36">
                                <div class="text-xs mono-num text-ink-2">{{ $task->completed_slots }} / {{ $task->available_slots }}</div>
                                <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-surface-3"><div class="h-full rounded-full bg-secondary" style="width: {{ intdiv($task->completed_slots * 100, max(1, $task->available_slots)) }}%"></div></div>
                            </td>
                            <td class="mono-num">{{ $task->reward->format() }}</td>
                            <td>@if ($task->pending_count)<x-badge color="amber">{{ $task->pending_count }}</x-badge>@else<span class="text-faint">—</span>@endif</td>
                            <td class="text-muted">{{ $task->created_at->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4">{{ $tasks->links() }}</div>
        @endif
    </div>
</x-layouts.app>
