<dl class="space-y-3 text-sm">
    <div class="flex gap-2"><dt class="text-muted">Outcome:</dt><dd><x-badge :color="['yes' => 'green', 'partially' => 'amber', 'no' => 'red'][$answer['reproduced'] ?? ''] ?? 'gray'">{{ \App\TaskTypes\Types\BugReproductionType::OUTCOMES[$answer['reproduced'] ?? ''] ?? '—' }}</x-badge></dd></div>
    <div class="flex gap-2"><dt class="text-muted">Environment:</dt><dd>{{ $answer['environment'] ?? '—' }}</dd></div>
    <div><dt class="text-xs font-medium text-muted">Notes</dt><dd class="mt-1 whitespace-pre-line rounded-lg border border-line bg-bg p-3">{{ $answer['notes'] ?? '' }}</dd></div>
</dl>
