<div class="space-y-3">
    @if (! empty($task->payload['source_url']))
        <a href="{{ $task->payload['source_url'] }}" target="_blank" rel="noopener noreferrer nofollow" class="flex items-center gap-2 rounded-lg border border-line bg-bg px-4 py-3 font-mono text-sm text-primary hover:border-primary/60">
            <x-icon name="book" class="size-4 shrink-0" /> <span class="truncate">{{ $task->payload['source_url'] }}</span>
        </a>
    @endif
    @if (! empty($task->payload['excerpt']))
        <div class="max-h-80 overflow-y-auto rounded-lg border border-line bg-bg p-4 text-sm leading-relaxed whitespace-pre-line text-ink-2">{{ $task->payload['excerpt'] }}</div>
    @endif
</div>
