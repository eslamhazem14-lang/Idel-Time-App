<dl class="space-y-3 text-sm">
    <div class="flex gap-2"><dt class="text-muted">Result:</dt><dd><x-badge :color="['pass' => 'green', 'partial' => 'amber', 'fail' => 'red'][$answer['result'] ?? ''] ?? 'gray'">{{ \App\TaskTypes\Types\WebsiteQaType::RESULTS[$answer['result'] ?? ''] ?? '—' }}</x-badge></dd></div>
    <div class="flex gap-2"><dt class="text-muted">Environment:</dt><dd>{{ $answer['environment'] ?? '—' }}</dd></div>
    <div><dt class="text-xs font-medium text-muted">Findings</dt><dd class="mt-1 whitespace-pre-line rounded-lg border border-line bg-bg p-3">{{ $answer['findings'] ?? '' }}</dd></div>
</dl>
