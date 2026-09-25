<div class="space-y-4">
    <x-textarea name="payload[question]" label="Question" rows="3" :value="$p['question'] ?? ''" />
    <x-textarea name="payload[options]" label="Options (one per line, 2–10)" rows="5" :value="is_array($p['options'] ?? null) ? implode(PHP_EOL, $p['options']) : ($p['options'] ?? '')" />
    <label class="flex items-center gap-2 text-sm text-ink-2">
        <input type="hidden" name="payload[multiple]" value="0">
        <input type="checkbox" name="payload[multiple]" value="1" class="rounded border-line bg-bg text-primary" @checked(filter_var($p['multiple'] ?? false, FILTER_VALIDATE_BOOLEAN))>
        Allow selecting multiple answers
    </label>
</div>
