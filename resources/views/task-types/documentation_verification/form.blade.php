<div class="space-y-4">
    <x-input name="payload[source_url]" type="url" label="Documentation URL (optional if you paste an excerpt)" :value="$p['source_url'] ?? ''" />
    <x-textarea name="payload[excerpt]" label="Documentation excerpt (optional)" rows="6" :value="$p['excerpt'] ?? ''" />
    <x-textarea name="payload[claims]" label="Claims to verify (one per line)" rows="5" :value="is_array($p['claims'] ?? null) ? implode(PHP_EOL, $p['claims']) : ($p['claims'] ?? '')" />
</div>
