<x-layouts.app :title="'Review: '.$task->title">
    <x-page-header :title="$task->title" :subtitle="'Task #'.$task->id.' · '.$task->handler()->label()" :back="route('admin.tasks.index')">
        <x-status :value="$task->status" />
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-[1fr_340px]">
        <div class="space-y-6">
            <div class="card card-pad">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-muted">Potential issues</h2>
                @if (empty($issues))
                    <p class="mt-2 flex items-center gap-2 text-sm text-secondary"><x-icon name="check-circle" /> No automated concerns.</p>
                @else
                    <ul class="mt-3 space-y-2">
                        @foreach ($issues as $issue)
                            <li class="flex items-start gap-2 rounded-lg px-3 py-2 text-sm {{ ['danger' => 'bg-danger/10 text-ink', 'warning' => 'bg-amber/10 text-ink', 'info' => 'bg-surface-2 text-ink-2'][$issue['level']] }}">
                                <x-icon :name="$issue['level'] === 'info' ? 'info' : 'alert'" class="mt-0.5 size-4 shrink-0 {{ ['danger' => 'text-danger', 'warning' => 'text-amber', 'info' => 'text-muted'][$issue['level']] }}" />
                                {{ $issue['message'] }}
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <div class="card card-pad">
                <p class="text-sm text-ink-2">{{ $task->description }}</p>
                <h2 class="mt-5 text-xs font-semibold uppercase tracking-wider text-muted">Instructions</h2>
                <div class="prose-task mt-2 whitespace-pre-line">{{ $task->instructions }}</div>
                @if ($task->answer_format)<p class="mt-4 text-sm"><span class="text-muted">Answer format:</span> {{ $task->answer_format }}</p>@endif
                <h2 class="mb-2 mt-5 text-xs font-semibold uppercase tracking-wider text-muted">Content</h2>
                @include($task->handler()->view('content'), ['task' => $task])
                @if ($task->attachments->isNotEmpty())<div class="mt-4"><x-attachments :items="$task->attachments" /></div>@endif
            </div>
            @if ($task->reports->isNotEmpty())
                <div class="card">
                    <h2 class="border-b border-line px-5 py-3 text-sm font-semibold">Reports ({{ $task->reports->count() }})</h2>
                    @foreach ($task->reports as $report)
                        <div class="border-b border-line/60 px-5 py-3 text-sm last:border-0">
                            <div class="flex items-center gap-2">{{ $report->reason->label() }} <x-status :value="$report->status" /></div>
                            <p class="text-muted">{{ $report->description }}</p>
                            <div class="text-xs text-faint">by {{ $report->reporter->name }} · {{ $report->created_at->diffForHumans() }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
            @if ($submissions->isNotEmpty())
                <div class="card overflow-x-auto">
                    <h2 class="border-b border-line px-5 py-3 text-sm font-semibold">Submissions</h2>
                    <table class="table">
                        <tbody>
                            @foreach ($submissions as $s)
                                <tr>
                                    <td><a href="{{ route('admin.submissions.show', $s) }}" class="hover:text-primary">#{{ $s->id }} · {{ $s->developer->name }}</a></td>
                                    <td class="text-muted">{{ $s->submitted_at->diffForHumans() }}</td>
                                    <td><x-status :value="$s->status" /> @if ($s->is_flagged)<x-badge color="red">Flagged</x-badge>@endif</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
            <div class="card card-pad space-y-3" x-data="{ mode: null }">
                <h2 class="text-sm font-semibold">Moderation</h2>
                @if ($task->status === \App\Enums\TaskStatus::PendingApproval)
                    <form method="POST" action="{{ route('admin.tasks.approve', $task) }}">@csrf<button class="btn-success w-full"><x-icon name="check" /> Approve & publish</button></form>
                    <button type="button" class="btn-danger w-full" @click="mode = 'reject'">Reject</button>
                    <form x-show="mode === 'reject'" x-cloak method="POST" action="{{ route('admin.tasks.reject', $task) }}" class="space-y-2">
                        @csrf
                        <x-textarea name="reason" label="Rejection reason (sent to requester)" rows="3" required />
                        <button class="btn-danger w-full">Confirm rejection & refund</button>
                    </form>
                @endif
                @if (in_array($task->status, [\App\Enums\TaskStatus::Active, \App\Enums\TaskStatus::Paused, \App\Enums\TaskStatus::PendingApproval]))
                    <button type="button" class="btn-secondary w-full" @click="mode = 'suspend'"><x-icon name="pause" /> Suspend</button>
                    <form x-show="mode === 'suspend'" x-cloak method="POST" action="{{ route('admin.tasks.suspend', $task) }}" class="space-y-2">
                        @csrf
                        <x-textarea name="reason" label="Suspension reason" rows="3" required />
                        <button class="btn-danger w-full">Suspend task</button>
                    </form>
                @endif
                @if ($task->status === \App\Enums\TaskStatus::Suspended)
                    <form method="POST" action="{{ route('admin.tasks.reinstate', $task) }}">@csrf<button class="btn-success w-full">Reinstate</button></form>
                    <p class="text-xs text-muted">Suspended: {{ $task->rejection_reason }}</p>
                @endif
                @unless ($task->status->isClosed())
                    <form method="POST" action="{{ route('admin.tasks.cancel', $task) }}" onsubmit="return confirm('Cancel this task and refund unused budget?')">@csrf<button class="btn-ghost w-full text-danger">Cancel & refund</button></form>
                @else
                    <p class="text-sm text-muted">This task is closed.</p>
                @endunless
            </div>
            <div class="card card-pad text-sm">
                <h2 class="mb-3 font-semibold">Economics</h2>
                <dl class="space-y-2">
                    <div class="flex justify-between"><dt class="text-muted">Reward</dt><dd class="mono-num">{{ $task->reward->format() }} <span class="text-faint">({{ $task->rewardPerMinute() }})</span></dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Fee</dt><dd class="mono-num">{{ $task->platform_fee->format() }} ({{ $task->commission_percent }}%)</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Slots</dt><dd class="mono-num">{{ $task->available_slots }} ({{ $task->reserved_slots }} reserved, {{ $task->completed_slots }} done)</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Total budget</dt><dd class="mono-num">{{ $task->total_budget->format() }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Escrow</dt><dd class="mono-num">{{ $task->escrow_balance->format() }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Estimate</dt><dd>{{ $task->estimated_minutes }} min · {{ $task->difficulty->label() }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Skills</dt><dd class="text-right">{{ implode(', ', $task->required_skills ?? []) ?: '—' }}</dd></div>
                    @if ($task->deadline)<div class="flex justify-between"><dt class="text-muted">Deadline</dt><dd>{{ $task->deadline->format('M j, H:i') }}</dd></div>@endif
                </dl>
            </div>
            <div class="card card-pad text-sm">
                <h2 class="mb-3 font-semibold">Requester</h2>
                <a href="{{ route('admin.users.show', $task->requester) }}" class="font-medium hover:text-primary">{{ $task->requester->name }}</a>
                <div class="text-xs text-faint">{{ $task->requester->email }}</div>
                <dl class="mt-3 space-y-2">
                    <div class="flex justify-between"><dt class="text-muted">Tasks posted</dt><dd>{{ $requesterStats['tasks'] }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Previously rejected</dt><dd>{{ $requesterStats['rejected'] }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Available funds</dt><dd class="mono-num">{{ $task->requester->wallet?->balance->format() }}</dd></div>
                </dl>
            </div>
        </aside>
    </div>
</x-layouts.app>
