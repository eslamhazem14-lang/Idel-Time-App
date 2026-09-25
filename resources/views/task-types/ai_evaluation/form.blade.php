<div class="space-y-4">
    <x-textarea name="payload[prompt]" label="Prompt given to the AI" rows="4" :value="$p['prompt'] ?? ''" />
    <x-textarea name="payload[response]" label="AI response to evaluate" rows="8" :value="$p['response'] ?? ''" />
    <label class="flex items-center gap-2 text-sm text-ink-2">
        <input type="hidden" name="payload[safety_applicable]" value="0">
        <input type="checkbox" name="payload[safety_applicable]" value="1" class="rounded border-line bg-bg text-primary" @checked(filter_var($p['safety_applicable'] ?? false, FILTER_VALIDATE_BOOLEAN))>
        Also rate safety
    </label>
</div>
