@php $s = $dispute->submission; @endphp
<x-layouts.app :title="'Dispute #'.$dispute->id">
    <x-page-header :title="'Appeal on “'.$s->task->title.'”'" :subtitle="'By '.$dispute->developer->name.' · '.$dispute->created_at->format('M j, Y')" :back="route('admin.disputes.index')">
        <x-status :value="$dispute->status" />
    </x-page-header>
    <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
        <div class="space-y-6">
            <div class="card card-pad">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-muted">Rejection</h2>
                <p class="mt-2 text-sm">{{ $s->rejection_reason }}</p>
                <p class="mt-1 text-xs text-faint">by {{ $s->reviewer?->name ?? 'unknown' }} · {{ $s->reviewed_at?->diffForHumans() }}</p>
                <h2 class="mt-5 text-xs font-semibold uppercase tracking-wider text-muted">Developer's appeal</h2>
                <p class="mt-2 whitespace-pre-line text-sm">{{ $dispute->reason }}</p>
            </div>
            <div class="card card-pad">
                <h2 class="mb-3 text-xs font-semibold uppercase tracking-wider text-muted">Submitted answer</h2>
                <x-answer-display :submission="$s" />
                @if ($s->attachments->isNotEmpty())<div class="mt-4"><x-attachments :items="$s->attachments" /></div>@endif
            </div>
            <details class="card card-pad">
                <summary class="cursor-pointer text-sm font-semibold">Task instructions & material</summary>
                <div class="mt-3 space-y-3"><p class="prose-task whitespace-pre-line">{{ $s->task->instructions }}</p>@include($s->task->handler()->view('content'), ['task' => $s->task])</div>
            </details>
        </div>
        <aside>
            @if ($dispute->status->value === 'open')
                <form method="POST" action="{{ route('admin.disputes.resolve', $dispute) }}" class="card card-pad space-y-3">
                    @csrf
                    <h2 class="text-sm font-semibold">Resolve</h2>
                    <x-textarea name="note" label="Resolution note (sent to developer)" rows="3" required />
                    <button name="decision" value="uphold" class="btn-success w-full">Uphold — approve & pay {{ $s->reward->format() }}</button>
                    <button name="decision" value="deny" class="btn-danger w-full">Deny appeal</button>
                </form>
            @else
                <div class="card card-pad text-sm">
                    <div class="font-medium">{{ $dispute->status->label() }}</div>
                    <p class="mt-2 text-ink-2">{{ $dispute->resolution_note }}</p>
                    <p class="mt-1 text-xs text-faint">by {{ $dispute->resolver?->name }} · {{ $dispute->resolved_at?->diffForHumans() }}</p>
                </div>
            @endif
        </aside>
    </div>
</x-layouts.app>
