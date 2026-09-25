<div class="space-y-4">
    <x-select name="answer[reproduced]" label="Outcome" :options="\App\TaskTypes\Types\BugReproductionType::OUTCOMES" :value="$draft['reproduced'] ?? ''" placeholder="Choose…" required />
    <x-input name="answer[environment]" label="Your environment" :value="$draft['environment'] ?? ''" placeholder="OS, runtime, browser versions" required />
    <x-textarea name="answer[notes]" label="What happened" rows="6" :value="$draft['notes'] ?? ''" hint="Include error output or deviations from the steps." required />
</div>
