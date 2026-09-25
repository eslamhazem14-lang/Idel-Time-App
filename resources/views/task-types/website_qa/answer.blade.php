<div class="space-y-4">
    <x-select name="answer[result]" label="Result" :options="\App\TaskTypes\Types\WebsiteQaType::RESULTS" :value="$draft['result'] ?? ''" placeholder="Choose…" required />
    <x-input name="answer[environment]" label="Environment tested" :value="$draft['environment'] ?? ''" placeholder="e.g. Chrome 128 / macOS, Safari iOS 18" required />
    <x-textarea name="answer[findings]" label="Findings" rows="6" :value="$draft['findings'] ?? ''" hint="What worked, what broke, with steps." required />
</div>
