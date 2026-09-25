<div>
    @if (! empty($task->payload['language']))<div class="mb-2"><x-badge>{{ $task->payload['language'] }}</x-badge></div>@endif
    <pre class="code-block"><code>{{ $task->payload['code'] ?? '' }}</code></pre>
</div>
