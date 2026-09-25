<x-layouts.app :title="'Submission #'.$submission->id">
    <x-page-header :title="$task->title" :subtitle="'Submission #'.$submission->id.' · '.$submission->submitted_at->format('M j, Y H:i')" :back="route('developer.submissions.index')">
        <x-status :value="$submission->status" />
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
        <div class="space-y-6">
            @if ($submission->status === \App\Enums\SubmissionStatus::Rejected)
                <div class="rounded-xl border border-danger/30 bg-danger/10 p-5">
                    <div class="flex items-center gap-2 font-medium text-danger"><x-icon name="x" /> Rejected</div>
                    <p class="mt-2 text-sm text-ink">{{ $submission->rejection_reason }}</p>
                    @if ($submission->dispute)
                        <div class="mt-4 rounded-lg border border-line bg-surface p-3 text-sm">
                            <div class="flex items-center gap-2">Appeal: <x-status :value="$submission->dispute->status" /></div>
                            @if ($submission->dispute->resolution_note)<p class="mt-2 text-muted">{{ $submission->dispute->resolution_note }}</p>@endif
                        </div>
                    @elseif ($canAppeal)
                        <button type="button" class="btn-secondary mt-4" @click="$dispatch('open-modal', 'appeal')"><x-icon name="scale" /> Appeal this decision</button>
                    @endif
                </div>
            @elseif ($submission->status === \App\Enums\SubmissionStatus::Approved)
                <div class="rounded-xl border border-secondary/30 bg-secondary/10 p-5 text-sm">
                    <div class="flex items-center gap-2 font-medium text-secondary"><x-icon name="check-circle" /> Approved{{ $submission->auto_approved ? ' automatically' : '' }}</div>
                    <p class="mt-1 text-ink-2">{{ $submission->reward->format() }} was added to your available balance.</p>
                </div>
            @elseif ($submission->status === \App\Enums\SubmissionStatus::RevisionRequested)
                <div class="rounded-xl border border-primary/30 bg-primary/10 p-5 text-sm">
                    <div class="font-medium">Revision requested</div>
                    <p class="mt-1 text-ink-2">{{ $submission->revision_note }}</p>
                    @if ($submission->claim->isActive())<a href="{{ route('developer.work.show', $submission->claim) }}" class="btn-primary mt-4">Update your answer</a>@endif
                </div>
            @else
                <div class="rounded-xl border border-amber/30 bg-amber/10 p-5 text-sm">
                    <div class="flex items-center gap-2 font-medium text-amber"><x-icon name="clock" /> Pending review</div>
                    <p class="mt-1 text-ink-2">The requester will review your work. Unreviewed submissions are approved automatically after {{ (int) settings('auto_approve_days') }} days.</p>
                </div>
            @endif

            <div class="card card-pad">
                <h2 class="mb-3 text-xs font-semibold uppercase tracking-wider text-muted">Your answer</h2>
                <x-answer-display :submission="$submission" />
                @if ($submission->attachments->isNotEmpty())<div class="mt-4"><x-attachments :items="$submission->attachments" /></div>@endif
            </div>
        </div>
        <aside class="space-y-4">
            <div class="card card-pad text-sm">
                <dl class="space-y-2">
                    <div class="flex justify-between"><dt class="text-muted">Reward</dt><dd class="font-semibold mono-num">{{ $submission->reward->format() }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Time spent</dt><dd class="mono-num">{{ $submission->timeSpentForHumans() }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Estimated</dt><dd>{{ $task->estimated_minutes }} min</dd></div>
                    @if ($submission->reviewed_at)<div class="flex justify-between"><dt class="text-muted">Reviewed</dt><dd>{{ $submission->reviewed_at->diffForHumans() }}</dd></div>@endif
                </dl>
            </div>
            @include('developer.tasks._rate', ['task' => $task, 'myReview' => $myReview])
        </aside>
    </div>

    @if ($canAppeal)
        <x-modal name="appeal" title="Appeal rejection">
            <form method="POST" action="{{ route('developer.submissions.appeal', $submission) }}" class="space-y-4">
                @csrf
                <p class="text-sm text-muted">Explain why your submission meets the task instructions. An administrator will review the task, your answer and the rejection reason.</p>
                <x-textarea name="reason" label="Your reasoning" rows="5" required />
                <div class="flex justify-end"><button class="btn-primary">Submit appeal</button></div>
            </form>
        </x-modal>
    @endif
</x-layouts.app>
