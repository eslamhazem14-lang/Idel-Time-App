<div class="space-y-4">
    <x-select name="answer[verdict]" label="Overall verdict" :options="\App\TaskTypes\Types\CodeReviewType::VERDICTS" :value="$draft['verdict'] ?? ''" placeholder="Choose…" required />
    @foreach ($task->payload['questions'] ?? [] as $i => $question)
        <x-textarea name="answer[answers][{{ $i }}]" :label="($i + 1).'. '.$question" rows="3" :value="$draft['answers'][$i] ?? ''" required />
    @endforeach
    <x-textarea name="answer[issues]" label="Issues found" rows="5" :value="$draft['issues'] ?? ''" hint="Required when requesting changes. Reference line numbers where possible." />
</div>
