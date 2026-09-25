<x-layouts.app title="Requester dashboard">
    <x-page-header title="Requester dashboard" subtitle="Your tasks, reviews and spending at a glance.">
        <a href="{{ route('requester.batches.create') }}" class="btn-secondary"><x-icon name="layers" /> New batch</a>
        <a href="{{ route('requester.tasks.create') }}" class="btn-primary"><x-icon name="plus" /> Post a Task</a>
    </x-page-header>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
        <x-stat label="Available funds" :value="$stats['available']->format()" icon="wallet" />
        <x-stat label="Remaining budget (escrow)" :value="$stats['escrow']->format()" icon="lock" />
        <x-stat label="Total spending" :value="$stats['spent']->format()" icon="coin" />
        <x-stat label="Active tasks" :value="$stats['active_tasks']" icon="layers" :hint="$stats['pending_approval'].' pending approval'" />
        <x-stat label="Pending submissions" :value="$stats['pending_submissions']" icon="inbox" :hint="$stats['completed_tasks'].' tasks completed'" class="col-span-2 lg:col-span-1" />
    </div>

    <div class="mt-3 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat label="Completion rate" :value="$stats['completion_rate'] !== null ? $stats['completion_rate'].'%' : '—'" hint="Claims that were submitted" />
        <x-stat label="Approval rate" :value="$stats['approval_rate'] !== null ? $stats['approval_rate'].'%' : '—'" :hint="$stats['approved'].' approved · '.$stats['rejected'].' rejected'" />
        <x-stat label="Avg. completion time" :value="$stats['avg_completion_seconds'] ? gmdate($stats['avg_completion_seconds'] >= 3600 ? 'H:i:s' : 'i:s', $stats['avg_completion_seconds']) : '—'" hint="mm:ss" />
        <x-stat label="Cost per approved task" :value="$stats['cost_per_approved']?->format() ?? '—'" hint="Reward + fee" />
    </div>

    @if ($stats['available']->isZero() && $stats['escrow']->isZero())
        <div class="mt-6 flex flex-col items-start justify-between gap-3 rounded-xl border border-primary/30 bg-primary/10 p-5 sm:flex-row sm:items-center">
            <div>
                <div class="font-medium">Add funds to post your first task</div>
                <p class="text-sm text-ink-2">Task budgets are reserved from your balance and only spent on approved work.</p>
            </div>
            <a href="{{ route('requester.billing') }}" class="btn-primary">Add funds</a>
        </div>
    @endif

    <div class="mt-8 grid gap-6 xl:grid-cols-2">
        <section class="card">
            <div class="flex items-center justify-between border-b border-line px-5 py-3.5">
                <h2 class="text-sm font-semibold">Waiting for your review</h2>
                <a href="{{ route('requester.submissions.index') }}" class="text-xs text-muted hover:text-ink">Review queue →</a>
            </div>
            @forelse ($pending as $s)
                <a href="{{ route('requester.submissions.show', $s) }}" class="flex items-center justify-between gap-3 border-b border-line/60 px-5 py-3 last:border-0 hover:bg-surface-2/60">
                    <div class="min-w-0">
                        <div class="truncate text-sm">{{ $s->task->title }}</div>
                        <div class="text-xs text-faint">{{ $s->developer->name }} · {{ $s->submitted_at->diffForHumans() }}</div>
                    </div>
                    <span class="btn-secondary btn-sm">Review</span>
                </a>
            @empty
                <x-empty icon="inbox" title="No submissions to review" text="New submissions will appear here as developers complete your tasks." class="!py-10" />
            @endforelse
        </section>
        <section class="card">
            <div class="flex items-center justify-between border-b border-line px-5 py-3.5">
                <h2 class="text-sm font-semibold">Recent tasks</h2>
                <a href="{{ route('requester.tasks.index') }}" class="text-xs text-muted hover:text-ink">All tasks →</a>
            </div>
            @forelse ($tasks as $task)
                <a href="{{ route('requester.tasks.show', $task) }}" class="flex items-center justify-between gap-3 border-b border-line/60 px-5 py-3 last:border-0 hover:bg-surface-2/60">
                    <div class="min-w-0">
                        <div class="truncate text-sm">{{ $task->title }}</div>
                        <div class="text-xs text-faint mono-num">{{ $task->completed_slots }}/{{ $task->available_slots }} completed · {{ $task->reward->format() }} each</div>
                    </div>
                    <x-status :value="$task->status" />
                </a>
            @empty
                <x-empty icon="layers" title="No tasks yet" text="Post a task and developers can start within minutes of approval.">
                    <a href="{{ route('requester.tasks.create') }}" class="btn-primary">Post a Task</a>
                </x-empty>
            @endforelse
        </section>
    </div>
</x-layouts.app>
