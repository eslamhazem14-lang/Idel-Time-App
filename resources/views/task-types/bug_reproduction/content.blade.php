<div class="space-y-3 text-sm">
    @if (! empty($task->payload['environment']))<p class="text-muted">Environment: <span class="text-ink-2">{{ $task->payload['environment'] }}</span></p>@endif
    <div>
        <div class="label">Steps</div>
        <pre class="code-block whitespace-pre-wrap">{{ $task->payload['steps'] ?? '' }}</pre>
    </div>
    <div class="grid gap-3 sm:grid-cols-2">
        <div class="rounded-lg border border-line bg-bg p-3"><div class="text-xs text-muted">Expected</div><div class="mt-1 whitespace-pre-line">{{ $task->payload['expected'] ?? '' }}</div></div>
        @if (! empty($task->payload['actual']))<div class="rounded-lg border border-line bg-bg p-3"><div class="text-xs text-muted">Reported actual</div><div class="mt-1 whitespace-pre-line">{{ $task->payload['actual'] }}</div></div>@endif
    </div>
</div>
