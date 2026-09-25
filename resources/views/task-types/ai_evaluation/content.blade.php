<div class="space-y-3">
    <div>
        <div class="label">Prompt</div>
        <div class="rounded-lg border border-line bg-bg p-4 text-sm leading-relaxed whitespace-pre-line">{{ $task->payload['prompt'] ?? '' }}</div>
    </div>
    <div>
        <div class="label flex items-center gap-1.5"><x-icon name="sparkles" class="size-3.5 text-primary" /> AI response</div>
        <div class="rounded-lg border border-primary/25 bg-primary/5 p-4 text-sm leading-relaxed whitespace-pre-line">{{ $task->payload['response'] ?? '' }}</div>
    </div>
</div>
