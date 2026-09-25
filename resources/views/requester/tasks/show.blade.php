<x-layouts.app :title="$task->title">
    <x-page-header :title="$task->title" :back="route('requester.tasks.index')">
        <x-status :value="$task->status" />
        @can('update', $task)<a href="{{ route('requester.tasks.edit', $task) }}" class="btn-secondary"><x-icon name="edit" /> Edit</a>@endcan
        @if ($task->status === \App\Enums\TaskStatus::Active)
            <form method="POST" action="{{ route('requester.tasks.pause', $task) }}">@csrf<button class="btn-secondary"><x-icon name="pause" /> Pause</button></form>
        @elseif ($task->status === \App\Enums\TaskStatus::Paused)
            <form method="POST" action="{{ route('requester.tasks.resume', $task) }}">@csrf<button class="btn-secondary"><x-icon name="play" /> Resume</button></form>
        @endif
        @if (in_array($task->status, [\App\Enums\TaskStatus::Active, \App\Enums\TaskStatus::Paused, \App\Enums\TaskStatus::Completed, \App\Enums\TaskStatus::PendingApproval]))
            <button type="button" class="btn-secondary" @click="$dispatch('open-modal', 'slots')"><x-icon name="plus" /> Add slots</button>
        @endif
        @unless ($task->status->isClosed())
            <button type="button" class="btn-danger" @click="$dispatch('open-modal', 'cancel')">Cancel task</button>
        @endunless
    </x-page-header>

    @if ($task->status === \App\Enums\TaskStatus::Rejected || $task->status === \App\Enums\TaskStatus::Suspended)
        <div class="mb-6 rounded-xl border border-danger/30 bg-danger/10 p-4 text-sm">
            <div class="font-medium text-danger">{{ $task->status === \App\Enums\TaskStatus::Rejected ? 'Not approved' : 'Suspended by moderation' }}</div>
            <p class="mt-1 text-ink">{{ $task->rejection_reason }}</p>
        </div>
    @elseif ($task->status === \App\Enums\TaskStatus::PendingApproval)
        <div class="mb-6 rounded-xl border border-amber/30 bg-amber/10 p-4 text-sm text-ink">Waiting for moderation. We usually review new tasks within a few hours.</div>
    @endif

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-6">
        <x-stat label="Completed" :value="$task->completed_slots.' / '.$task->available_slots" />
        <x-stat label="In progress" :value="$activeClaims" />
        <x-stat label="To review" :value="$analytics['pending_submissions']" />
        <x-stat label="Completion rate" :value="$analytics['completion_rate'] !== null ? $analytics['completion_rate'].'%' : '—'" />
        <x-stat label="Approval rate" :value="$analytics['approval_rate'] !== null ? $analytics['approval_rate'].'%' : '—'" />
        <x-stat label="Avg. time" :value="$analytics['avg_completion_seconds'] ? gmdate('i:s', min($analytics['avg_completion_seconds'], 3599)) : '—'" :hint="'est. '.$task->estimated_minutes.' min'" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_320px]">
        <section class="card">
            <div class="border-b border-line px-5 py-3.5"><h2 class="text-sm font-semibold">Submissions</h2></div>
            @if ($submissions->isEmpty())
                <x-empty icon="inbox" title="No submissions yet" :text="$task->status === \App\Enums\TaskStatus::Active ? 'Developers can see this task now. Submissions will appear here.' : 'Submissions appear once the task is live.'" />
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Developer</th><th>Submitted</th><th>Time</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($submissions as $s)
                                <tr>
                                    <td>{{ $s->developer->name }} @if ($s->is_flagged)<x-badge color="red">Flagged</x-badge>@endif</td>
                                    <td class="text-muted">{{ $s->submitted_at->diffForHumans() }}</td>
                                    <td class="mono-num text-muted">{{ $s->timeSpentForHumans() }}</td>
                                    <td><x-status :value="$s->status" /></td>
                                    <td class="text-right"><a href="{{ route('requester.submissions.show', $s) }}" class="btn-secondary btn-sm">{{ $s->isPending() ? 'Review' : 'View' }}</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4">{{ $submissions->links() }}</div>
            @endif
        </section>

        <aside class="space-y-4">
            <div class="card card-pad text-sm">
                <h2 class="mb-3 font-semibold">Budget</h2>
                <dl class="space-y-2">
                    <div class="flex justify-between"><dt class="text-muted">Reward</dt><dd class="mono-num">{{ $task->reward->format() }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Fee ({{ rtrim(rtrim($task->commission_percent, '0'), '.') }}%)</dt><dd class="mono-num">{{ $task->platform_fee->format() }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Total budget</dt><dd class="mono-num">{{ $task->total_budget->format() }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Held in escrow</dt><dd class="mono-num">{{ $task->escrow_balance->format() }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Spent</dt><dd class="mono-num">{{ ($analytics['cost_per_approved'] ?? \App\Support\Money::zero())->multiply($analytics['approved'])->format() }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Cost / approved</dt><dd class="mono-num">{{ $analytics['cost_per_approved']?->format() ?? '—' }}</dd></div>
                </dl>
            </div>
            <div class="card card-pad text-sm">
                <h2 class="mb-2 font-semibold">Details</h2>
                <dl class="space-y-2">
                    <div class="flex justify-between"><dt class="text-muted">Type</dt><dd>{{ $task->handler()->label() }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Category</dt><dd>{{ $task->category->name }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Estimate</dt><dd>{{ $task->estimated_minutes }} min</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Difficulty</dt><dd>{{ $task->difficulty->label() }}</dd></div>
                    @if ($task->deadline)<div class="flex justify-between"><dt class="text-muted">Deadline</dt><dd>{{ $task->deadline->format('M j, H:i') }}</dd></div>@endif
                    @if ($task->batch)<div class="flex justify-between"><dt class="text-muted">Batch</dt><dd><a class="hover:text-primary" href="{{ route('requester.batches.show', $task->batch) }}">{{ $task->batch->title }}</a></dd></div>@endif
                    @if ($analytics['rating'])<div class="flex justify-between"><dt class="text-muted">Developer rating</dt><dd>{{ number_format($analytics['rating'], 1) }} / 5</dd></div>@endif
                </dl>
            </div>
            <details class="card card-pad text-sm">
                <summary class="cursor-pointer font-semibold">Task content preview</summary>
                <div class="mt-3 space-y-3">
                    <p class="text-ink-2 whitespace-pre-line">{{ $task->instructions }}</p>
                    @include($task->handler()->view('content'), ['task' => $task])
                    <x-attachments :items="$task->attachments" />
                </div>
            </details>
        </aside>
    </div>

    <x-modal name="slots" title="Add worker slots">
        <form method="POST" action="{{ route('requester.tasks.slots', $task) }}" class="space-y-4" x-data="{ n: 5 }">
            @csrf
            <x-input name="slots" type="number" label="Additional slots" min="1" x-model.number="n" value="5" />
            <p class="text-sm text-muted">Each slot reserves {{ $task->unitCost()->format() }} (reward + fee) from your balance.</p>
            <div class="flex justify-end"><button class="btn-primary">Add slots</button></div>
        </form>
    </x-modal>
    <x-modal name="cancel" title="Cancel this task?">
        <p class="text-sm text-muted">Developers currently working on it will be stopped. Submissions already received can still be reviewed. Unused budget returns to your balance.</p>
        <form method="POST" action="{{ route('requester.tasks.cancel', $task) }}" class="mt-5 flex justify-end">@csrf<button class="btn-danger">Cancel task</button></form>
    </x-modal>
</x-layouts.app>
