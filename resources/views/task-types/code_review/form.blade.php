<div class="space-y-4">
    <x-input name="payload[language]" label="Language" :value="$p['language'] ?? ''" placeholder="php, typescript, python…" />
    <x-textarea name="payload[code]" label="Code snippet" rows="12" class="font-mono text-[13px]" :value="$p['code'] ?? ''" />
    <x-textarea name="payload[questions]" label="Questions for the reviewer (one per line, optional)" rows="4" :value="is_array($p['questions'] ?? null) ? implode(PHP_EOL, $p['questions']) : ($p['questions'] ?? '')" />
</div>
