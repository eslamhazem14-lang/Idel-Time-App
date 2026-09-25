<div class="space-y-3 text-sm">
    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
        @foreach ($task->handler()->criteria($task) as $key => $label)
            <div class="rounded-lg border border-line bg-bg p-3">
                <div class="text-xs text-muted">{{ $label }}</div>
                <div class="mt-1 text-lg font-semibold mono-num">{{ $answer[$key] ?? '—' }}<span class="text-xs text-faint">/5</span></div>
            </div>
        @endforeach
    </div>
    @if (! empty($answer['comments']))<div class="whitespace-pre-line rounded-lg border border-line bg-bg p-3">{{ $answer['comments'] }}</div>@endif
</div>
