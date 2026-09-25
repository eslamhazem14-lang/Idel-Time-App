<x-layouts.app :title="$task->title">
    <x-page-header :title="$task->title" :back="route('developer.tasks.index')">
        <button type="button" class="btn-ghost" @click="$dispatch('open-modal', 'report')"><x-icon name="flag" /> Report a problem</button>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
        <div class="space-y-6">
            <div class="card card-pad">
                <div class="flex flex-wrap gap-2">
                    <x-badge color="violet"><x-icon :name="$task->category->icon" class="size-3" /> {{ $task->category->name }}</x-badge>
                    <x-badge>{{ $task->handler()->label() }}</x-badge>
                    <x-badge :color="$task->difficulty->color()">{{ $task->difficulty->label() }}</x-badge>
                    @foreach ($task->required_skills ?? [] as $skill)<x-badge>{{ $skill }}</x-badge>@endforeach
                </div>
                <p class="mt-4 text-sm leading-relaxed text-ink-2">{{ $task->description }}</p>
                <h2 class="mt-6 text-xs font-semibold uppercase tracking-wider text-muted">Instructions</h2>
                <div class="prose-task mt-2 whitespace-pre-line">{{ $task->instructions }}</div>
                @if ($task->answer_format)
                    <h2 class="mt-6 text-xs font-semibold uppercase tracking-wider text-muted">Expected answer format</h2>
                    <p class="mt-2 text-sm text-ink-2 whitespace-pre-line">{{ $task->answer_format }}</p>
                @endif
                @if ($task->attachments->isNotEmpty())
                    <h2 class="mt-6 mb-2 text-xs font-semibold uppercase tracking-wider text-muted">Attachments</h2>
                    <x-attachments :items="$task->attachments" />
                @endif
            </div>
            <div class="card card-pad">
                <h2 class="flex items-center gap-2 text-sm font-semibold"><x-icon name="lock" class="text-muted" /> Task content</h2>
                <p class="mt-1 text-xs text-muted">The full material is revealed when you start the task, so the timer reflects real work.</p>
            </div>
        </div>

        <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
            <div class="card card-pad">
                <div class="text-xs text-muted">Reward</div>
                <div class="mt-1 text-3xl font-semibold text-secondary mono-num">{{ $task->reward->format() }}</div>
                <div class="mt-1 text-xs text-faint mono-num">{{ $task->rewardPerMinute() }}</div>
                <dl class="mt-5 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-muted">Estimated time</dt><dd>{{ $task->estimated_minutes }} min</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Time limit</dt><dd>{{ $claimMinutes }} min</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Slots left</dt><dd class="mono-num">{{ $task->remainingSlots() }}</dd></div>
                    @if ($task->deadline)<div class="flex justify-between"><dt class="text-muted">Deadline</dt><dd>{{ $task->deadline->diffForHumans() }}</dd></div>@endif
                    @if ($rating)<div class="flex justify-between"><dt class="text-muted">Developer rating</dt><dd>{{ number_format($rating, 1) }} / 5</dd></div>@endif
                </dl>

                <div class="mt-6">
                    @if ($myClaim && $myClaim->isActive())
                        <a href="{{ route('developer.work.show', $myClaim) }}" class="btn-primary w-full">Continue task <x-icon name="arrow-right" /></a>
                    @elseif ($myClaim)
                        <div class="rounded-lg border border-line bg-surface-2 p-3 text-center text-sm text-muted">You already worked on this task ({{ strtolower($myClaim->status->label()) }}).</div>
                        @if ($myClaim->latestSubmission)
                            <a href="{{ route('developer.submissions.show', $myClaim->latestSubmission) }}" class="btn-secondary mt-2 w-full">View submission</a>
                        @endif
                    @elseif ($task->isClaimable())
                        <form method="POST" action="{{ route('developer.tasks.claim', $task) }}">
                            @csrf
                            <button class="btn-primary btn-lg w-full"><x-icon name="play" class="size-4" /> Start Task</button>
                        </form>
                        <p class="mt-2 text-center text-[11px] text-faint">The timer starts immediately. You can release the task if you change your mind.</p>
                    @else
                        <div class="rounded-lg border border-line bg-surface-2 p-3 text-center text-sm text-muted">This task is no longer available.</div>
                    @endif
                </div>
            </div>
            @if ($myClaim && $myClaim->latestSubmission)
                @include('developer.tasks._rate', ['task' => $task, 'myReview' => $myReview])
            @endif
        </aside>
    </div>

    <x-modal name="report" title="Report a problem with this task">
        <form method="POST" action="{{ route('developer.tasks.report', $task) }}" class="space-y-4">
            @csrf
            <x-select name="reason" label="What's wrong?" :options="collect(\App\Enums\ReportReason::cases())->reject(fn ($r) => $r === \App\Enums\ReportReason::UnfairReview)->mapWithKeys(fn ($r) => [$r->value => $r->label()])->all()" />
            <x-textarea name="description" label="Details (optional)" rows="4" />
            <div class="flex justify-end"><button class="btn-primary">Send report</button></div>
        </form>
    </x-modal>
</x-layouts.app>
