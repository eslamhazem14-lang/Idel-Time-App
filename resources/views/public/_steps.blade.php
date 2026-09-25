@php
    $steps = [
        ['terminal', 'Start your AI task', 'Kick off a Claude Code, Cursor or Codex run as usual.'],
        ['globe', 'Open the platform', 'See tasks sized to the minutes you have right now.'],
        ['search', 'Pick a microtask', 'Sorted by reward per minute. No applications, no bidding.'],
        ['code', 'Complete it', 'Review code, rate an AI answer, test a page — 2 to 15 minutes.'],
        ['check', 'Submit', 'Your answer is locked in and the reward goes to pending.'],
        ['coin', 'Get paid after approval', 'Approved work moves to your available balance.'],
    ];
@endphp
<ol class="grid gap-px overflow-hidden rounded-xl border border-line bg-line sm:grid-cols-2 lg:grid-cols-3">
    @foreach ($steps as $i => [$icon, $title, $text])
        <li class="bg-surface p-5">
            <div class="flex items-center gap-3">
                <span class="font-mono text-xs text-faint">0{{ $i + 1 }}</span>
                <x-icon :name="$icon" class="size-4 text-primary" />
            </div>
            <h3 class="mt-3 text-sm font-semibold">{{ $title }}</h3>
            <p class="mt-1 text-sm text-muted">{{ $text }}</p>
        </li>
    @endforeach
</ol>
