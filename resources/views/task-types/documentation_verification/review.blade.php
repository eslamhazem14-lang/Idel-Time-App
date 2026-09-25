<div class="space-y-2 text-sm">
    @foreach ($task->payload['claims'] ?? [] as $i => $claim)
        @php $v = $answer['verdicts'][$i] ?? null; @endphp
        <div class="flex items-start justify-between gap-3 rounded-lg border border-line bg-bg p-3">
            <span>{{ $claim }}</span>
            <x-badge :color="['accurate' => 'green', 'inaccurate' => 'red', 'unverifiable' => 'amber'][$v] ?? 'gray'">{{ \App\TaskTypes\Types\DocumentationVerificationType::VERDICTS[$v] ?? '—' }}</x-badge>
        </div>
    @endforeach
    @if (! empty($answer['notes']))<div class="whitespace-pre-line rounded-lg border border-line bg-bg p-3">{{ $answer['notes'] }}</div>@endif
</div>
