<x-layouts.app :title="'Working: '.$task->title">
    @php $total = (int) $claim->claimed_at->diffInSeconds($claim->expires_at); @endphp
    <div x-data="countdown('{{ $claim->expires_at->toIso8601String() }}', '{{ now()->toIso8601String() }}', {{ $total }})">
        {{-- Sticky timer bar --}}
        <div class="sticky top-16 z-20 -mx-4 mb-6 border-b border-line bg-bg/90 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <div class="text-xs text-muted">{{ $task->category->name }} · {{ $task->handler()->label() }}</div>
                    <h1 class="truncate font-semibold">{{ $task->title }}</h1>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <div class="text-[11px] text-muted" x-text="expired ? 'Grace period' : 'Time left'"></div>
                        <div class="font-mono text-2xl font-semibold mono-num" :class="expired ? 'text-danger' : (urgent ? 'text-amber' : 'text-ink')" x-text="display"></div>
                    </div>
                    <div class="text-right">
                        <div class="text-[11px] text-muted">Reward</div>
                        <div class="text-lg font-semibold text-secondary mono-num">{{ $task->reward->format() }}</div>
                    </div>
                </div>
            </div>
            <div class="mt-2 h-1 overflow-hidden rounded-full bg-surface-3">
                <div class="h-full rounded-full transition-all duration-1000" :class="urgent ? 'bg-danger' : 'bg-primary'" :style="`width: ${percent}%`"></div>
            </div>
        </div>

        <div x-show="expired" x-cloak class="mb-5 flex items-center gap-3 rounded-lg border border-danger/30 bg-danger/10 px-4 py-3 text-sm">
            <x-icon name="alert" class="size-4 text-danger" />
            <span>Time is up. You have a short grace period ({{ $graceSeconds }}s) to submit — after that the task expires.</span>
        </div>

        @if ($revision && $revision->status === \App\Enums\SubmissionStatus::RevisionRequested)
            <div class="mb-5 rounded-lg border border-primary/30 bg-primary/10 px-4 py-3 text-sm">
                <div class="font-medium text-ink">Revision requested</div>
                <p class="mt-1 text-ink-2">{{ $revision->revision_note }}</p>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="space-y-5">
                <div class="card card-pad">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-muted">Instructions</h2>
                    <div class="prose-task mt-2 whitespace-pre-line">{{ $task->instructions }}</div>
                    @if ($task->answer_format)
                        <p class="mt-4 rounded-lg bg-surface-2 px-3 py-2 text-xs text-ink-2"><span class="text-muted">Expected format:</span> {{ $task->answer_format }}</p>
                    @endif
                </div>
                <div class="card card-pad">
                    <h2 class="mb-3 text-xs font-semibold uppercase tracking-wider text-muted">Material</h2>
                    @include($task->handler()->view('content'), ['task' => $task])
                    @if ($task->attachments->isNotEmpty())
                        <div class="mt-4"><x-attachments :items="$task->attachments" /></div>
                    @endif
                </div>
            </section>

            <section>
                <form method="POST" action="{{ route('developer.work.submit', $claim) }}" enctype="multipart/form-data" class="card card-pad space-y-5 lg:sticky lg:top-44"
                      x-data="{ submitting: false }" @submit="submitting = true">
                    @csrf
                    <div x-data="draftSaver('{{ route('developer.work.draft', $claim) }}')" class="space-y-5">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-semibold">Your answer</h2>
                            <span class="text-[11px] text-faint" x-text="status"></span>
                        </div>
                        @include($task->handler()->view('answer'), ['task' => $task, 'draft' => old('answer', $claim->draft ?? [])])
                    </div>
                    @if ($task->handler()->allowsAttachments())
                        <div>
                            <label class="label" for="attachments">Screenshots / files (optional)</label>
                            <input id="attachments" type="file" name="attachments[]" multiple accept=".{{ implode(',.', config('platform.uploads.extensions')) }}" class="field file:mr-3 file:rounded-md file:border-0 file:bg-surface-3 file:px-3 file:py-1 file:text-xs file:text-ink">
                            <p class="mt-1 text-xs text-faint">Up to {{ config('platform.uploads.max_files') }} files, {{ intdiv(config('platform.uploads.max_kb'), 1024) }} MB each.</p>
                            @error('attachments.*')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                        </div>
                    @endif
                    <div class="flex flex-col-reverse gap-2 border-t border-line pt-4 sm:flex-row sm:items-center sm:justify-between">
                        <button type="button" class="btn-ghost" @click="$dispatch('open-modal', 'release')">Release task</button>
                        <button class="btn-success btn-lg" :disabled="submitting">
                            <x-icon name="check" /> <span x-text="submitting ? 'Submitting…' : 'Submit for review'"></span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <x-modal name="release" title="Release this task?">
        <p class="text-sm text-muted">The task goes back to the pool for other developers. You won't be able to claim it again.</p>
        <form method="POST" action="{{ route('developer.work.release', $claim) }}" class="mt-5 flex justify-end gap-2">
            @csrf
            <button type="button" class="btn-ghost" @click="open = false">Keep working</button>
            <button class="btn-danger">Release task</button>
        </form>
    </x-modal>
</x-layouts.app>
