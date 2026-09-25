{{-- Review screen shared by requesters and admins. Expects: $submission, $task, $routePrefix, $canReview, $history, optional $admin --}}
@php
    $dev = $submission->developer;
    $profile = $dev->developerProfile;
    $admin = $admin ?? false;
    $fast = $submission->time_spent_seconds < $task->estimated_minutes * 60 * config('platform.fraud.fast_completion_ratio');
@endphp
<div class="grid gap-6 lg:grid-cols-[1fr_340px]">
    <div class="space-y-6">
        @if ($submission->is_flagged)
            <div class="flex items-start gap-3 rounded-xl border border-danger/30 bg-danger/10 p-4 text-sm">
                <x-icon name="shield" class="mt-0.5 size-4 text-danger" />
                <div><div class="font-medium">Flagged by automated quality checks</div><p class="text-ink-2">This submission matched a fraud heuristic (e.g. unusually fast, duplicate or repeated answer). Review carefully — flags are signals, not verdicts.</p></div>
            </div>
        @endif
        <div class="card card-pad">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-muted">Submitted answer</h2>
                <span class="text-xs text-faint">{{ $submission->submitted_at->format('M j, Y H:i') }}</span>
            </div>
            <x-answer-display :submission="$submission" />
            @if ($submission->attachments->isNotEmpty())<div class="mt-4"><x-attachments :items="$submission->attachments" /></div>@endif
        </div>
        <details class="card card-pad">
            <summary class="cursor-pointer text-sm font-semibold">Task material & instructions</summary>
            <div class="mt-4 space-y-4">
                <p class="prose-task whitespace-pre-line">{{ $task->instructions }}</p>
                @include($task->handler()->view('content'), ['task' => $task])
            </div>
        </details>
        @if ($history->isNotEmpty())
            <div class="card card-pad">
                <h2 class="mb-3 text-sm font-semibold">Earlier versions</h2>
                @foreach ($history as $old)
                    <div class="border-t border-line py-3 text-sm first:border-0 first:pt-0">
                        <div class="flex items-center gap-2"><x-status :value="$old->status" /> <span class="text-xs text-faint">{{ $old->submitted_at->format('M j H:i') }}</span></div>
                        @if ($old->revision_note)<p class="mt-1 text-muted">Revision note: {{ $old->revision_note }}</p>@endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
        @if ($canReview)
            <div class="card card-pad space-y-3" x-data="{ mode: null }">
                <h2 class="text-sm font-semibold">Decision</h2>
                <form method="POST" action="{{ route($routePrefix.'.approve', $submission) }}">
                    @csrf
                    <button class="btn-success w-full"><x-icon name="check" /> Approve & pay {{ $submission->reward->format() }}</button>
                </form>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" class="btn-secondary" @click="mode = mode === 'revision' ? null : 'revision'">Request revision</button>
                    <button type="button" class="btn-danger" @click="mode = mode === 'reject' ? null : 'reject'">Reject</button>
                </div>
                <form x-show="mode === 'reject'" x-cloak method="POST" action="{{ route($routePrefix.'.reject', $submission) }}" class="space-y-2">
                    @csrf
                    <x-textarea name="reason" label="Rejection reason (shown to the developer)" rows="3" required minlength="10" />
                    <button class="btn-danger w-full">Confirm rejection</button>
                </form>
                <form x-show="mode === 'revision'" x-cloak method="POST" action="{{ route($routePrefix.'.revision', $submission) }}" class="space-y-2">
                    @csrf
                    <x-textarea name="note" label="What should change?" rows="3" required minlength="10" />
                    <button class="btn-primary w-full">Send revision request</button>
                </form>
                <p class="text-[11px] text-faint">Rejecting re-opens the slot for another developer. The developer can appeal.</p>
            </div>
        @elseif ($submission->reviewed_at)
            <div class="card card-pad text-sm">
                <div class="flex items-center justify-between"><span class="text-muted">Decision</span><x-status :value="$submission->status" /></div>
                <div class="mt-2 text-xs text-faint">{{ $submission->auto_approved ? 'Auto-approved' : 'By '.($submission->reviewer?->name ?? 'system') }} · {{ $submission->reviewed_at->diffForHumans() }}</div>
                @if ($submission->rejection_reason)<p class="mt-3 text-ink-2">{{ $submission->rejection_reason }}</p>@endif
                @if ($submission->revision_note)<p class="mt-3 text-ink-2">{{ $submission->revision_note }}</p>@endif
            </div>
        @endif

        <div class="card card-pad text-sm">
            <h2 class="mb-3 font-semibold">Developer</h2>
            <div class="flex items-center gap-3">
                <span class="grid size-9 place-items-center rounded-full bg-surface-3 text-xs font-semibold">{{ $dev->initials() }}</span>
                <div><div>{{ $dev->name }}</div><div class="text-xs text-faint">Level {{ $dev->developer_level }}{{ $dev->country ? ' · '.$dev->country : '' }}</div></div>
            </div>
            <dl class="mt-4 space-y-2">
                <div class="flex justify-between"><dt class="text-muted">Approved tasks</dt><dd class="mono-num">{{ $profile?->completed_tasks ?? 0 }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">Rejected tasks</dt><dd class="mono-num">{{ $profile?->rejected_tasks ?? 0 }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">Approval rate</dt><dd class="mono-num">{{ $profile && ($profile->completed_tasks + $profile->rejected_tasks) ? $profile->approval_rate.'%' : '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">Time spent</dt><dd class="mono-num {{ $fast ? 'text-amber' : '' }}">{{ $submission->timeSpentForHumans() }} <span class="text-faint">/ est. {{ $task->estimated_minutes }}m</span></dd></div>
                @if ($admin)
                    <div class="flex justify-between"><dt class="text-muted">Fraud score</dt><dd class="mono-num {{ $dev->fraud_score ? 'text-danger' : '' }}">{{ $dev->fraud_score }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">IP</dt><dd class="font-mono text-xs">{{ $submission->ip_address ?? '—' }}</dd></div>
                @endif
            </dl>
            @if ($admin)<a href="{{ route('admin.users.show', $dev) }}" class="btn-secondary btn-sm mt-4 w-full">Open user profile</a>@endif
        </div>
    </aside>
</div>
