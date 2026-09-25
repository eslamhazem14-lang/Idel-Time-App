<div class="space-y-4 text-sm">
    <div><span class="text-muted">Verdict:</span> <x-badge :color="($answer['verdict'] ?? '') === 'approve' ? 'green' : (($answer['verdict'] ?? '') === 'request_changes' ? 'red' : 'gray')">{{ \App\TaskTypes\Types\CodeReviewType::VERDICTS[$answer['verdict'] ?? ''] ?? '—' }}</x-badge></div>
    @foreach ($task->payload['questions'] ?? [] as $i => $question)
        <div>
            <div class="text-xs font-medium text-muted">{{ $i + 1 }}. {{ $question }}</div>
            <div class="mt-1 whitespace-pre-line rounded-lg border border-line bg-bg p-3">{{ $answer['answers'][$i] ?? '—' }}</div>
        </div>
    @endforeach
    @if (! empty($answer['issues']))
        <div>
            <div class="text-xs font-medium text-muted">Issues found</div>
            <div class="mt-1 whitespace-pre-line rounded-lg border border-line bg-bg p-3">{{ $answer['issues'] }}</div>
        </div>
    @endif
</div>
