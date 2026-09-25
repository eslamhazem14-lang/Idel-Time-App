<div class="space-y-4">
    <x-input name="payload[url]" type="url" label="URL to test" :value="$p['url'] ?? ''" placeholder="https://staging.example.com" />
    <x-textarea name="payload[test_steps]" label="Test steps" rows="6" :value="$p['test_steps'] ?? ''" />
    <x-input name="payload[devices]" label="Devices / browsers (optional)" :value="$p['devices'] ?? ''" placeholder="Any modern browser; mobile preferred" />
</div>
