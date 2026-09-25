<x-layouts.app title="Tasks">
    <x-page-header title="Tasks" subtitle="Moderate new tasks and manage live ones." />
    @include('admin._tabs', ['route' => 'admin.tasks.index', 'cases' => \App\Enums\TaskStatus::cases(), 'current' => $status, 'counts' => $counts])

    @if ($pendingBatches->isNotEmpty() && $status === 'pending_approval')
        <div class="card mb-4">
            <h2 class="border-b border-line px-5 py-3 text-sm font-semibold">Batches awaiting approval</h2>
            @foreach ($pendingBatches as $b)
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line/60 px-5 py-3 text-sm last:border-0">
                    <div><span class="font-medium">{{ $b->title }}</span> <span class="text-muted">· {{ $b->total_tasks }} tasks · {{ $b->reward->format() }} each · by {{ $b->requester->name }}</span></div>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.tasks.index', ['batch' => $b->id]) }}" class="btn-secondary btn-sm">Inspect</a>
                        <form method="POST" action="{{ route('admin.batches.approve', $b) }}">@csrf<button class="btn-success btn-sm">Approve all</button></form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <form method="GET" class="mb-4 flex gap-2">
        <input type="hidden" name="status" value="{{ $status }}">
        <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search title" class="field max-w-xs">
        <button class="btn-secondary">Search</button>
    </form>

    <div class="card overflow-x-auto">
        @if ($tasks->isEmpty())
            <x-empty icon="layers" title="Nothing here" text="No tasks in this state." />
        @else
            <table class="table">
                <thead><tr><th>Task</th><th>Requester</th><th>Reward</th><th>Slots</th><th>Status</th><th>Created</th></tr></thead>
                <tbody>
                    @foreach ($tasks as $t)
                        <tr>
                            <td><a href="{{ route('admin.tasks.show', $t) }}" class="font-medium hover:text-primary">{{ $t->title }}</a><div class="text-xs text-faint">{{ $t->category->name }} · {{ $t->estimated_minutes }} min</div></td>
                            <td>{{ $t->requester->name }}</td>
                            <td class="mono-num">{{ $t->reward->format() }}</td>
                            <td class="mono-num">{{ $t->completed_slots }}/{{ $t->available_slots }}</td>
                            <td><x-status :value="$t->status" /></td>
                            <td class="text-muted">{{ $t->created_at->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4">{{ $tasks->links() }}</div>
        @endif
    </div>
</x-layouts.app>
