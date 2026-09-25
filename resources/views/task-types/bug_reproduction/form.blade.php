<div class="space-y-4">
    <x-input name="payload[environment]" label="Environment (optional)" :value="$p['environment'] ?? ''" placeholder="Node 20, macOS / Linux" />
    <x-textarea name="payload[steps]" label="Reproduction steps" rows="6" :value="$p['steps'] ?? ''" />
    <div class="grid gap-4 sm:grid-cols-2">
        <x-textarea name="payload[expected]" label="Expected result" rows="3" :value="$p['expected'] ?? ''" />
        <x-textarea name="payload[actual]" label="Reported actual result (optional)" rows="3" :value="$p['actual'] ?? ''" />
    </div>
</div>
