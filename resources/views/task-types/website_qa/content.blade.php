<div class="space-y-3">
    <a href="{{ $task->payload['url'] ?? '#' }}" target="_blank" rel="noopener noreferrer nofollow" class="flex items-center gap-2 rounded-lg border border-line bg-bg px-4 py-3 font-mono text-sm text-primary hover:border-primary/60">
        <x-icon name="external" class="size-4 shrink-0" /> <span class="truncate">{{ $task->payload['url'] ?? '' }}</span>
    </a>
    <div>
        <div class="label">Test steps</div>
        <div class="rounded-lg border border-line bg-bg p-4 text-sm leading-relaxed whitespace-pre-line text-ink-2">{{ $task->payload['test_steps'] ?? '' }}</div>
    </div>
    @if (! empty($task->payload['devices']))<p class="text-xs text-muted">Devices: {{ $task->payload['devices'] }}</p>@endif
</div>
