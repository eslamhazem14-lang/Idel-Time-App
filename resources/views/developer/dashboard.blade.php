<x-layouts.app title="Dashboard">
    <x-page-header title="Hi {{ explode(' ', auth()->user()->name)[0] }}" subtitle="Minutes matter. Here's what you can do while your agent works.">
        <a href="{{ route('developer.tasks.index') }}" class="btn-primary"><x-icon name="search" /> Browse all tasks</a>
    </x-page-header>

    @if ($activeClaim)
        <div x-data="countdown('{{ $activeClaim->expires_at->toIso8601String() }}', '{{ now()->toIso8601String() }}', {{ (int) $activeClaim->claimed_at->diffInSeconds($activeClaim->expires_at) }})"
             class="mb-6 flex flex-col gap-4 rounded-xl border border-primary/40 bg-primary/10 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="grid size-10 place-items-center rounded-lg bg-primary/20 text-primary"><x-icon name="clock" class="size-5" /></span>
                <div>
                    <div class="text-xs text-[#B3A1FF]">Task in progress</div>
                    <div class="font-medium">{{ $activeClaim->task->title }}</div>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <span class="font-mono text-xl mono-num" :class="urgent ? 'text-danger' : 'text-ink'" x-text="display"></span>
                <a href="{{ route('developer.work.show', $activeClaim) }}" class="btn-primary">Continue <x-icon name="arrow-right" /></a>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-6">
        <x-stat label="Available balance" :value="$stats['available']->format()" icon="wallet" accent class="col-span-2 lg:col-span-1" />
        <x-stat label="Pending" :value="$stats['pending']->format()" icon="clock" :hint="$stats['pending_reviews'].' awaiting review'" />
        <x-stat label="Today" :value="$stats['today']->format()" icon="bolt" />
        <x-stat label="Lifetime" :value="$stats['lifetime']->format()" icon="coin" />
        <x-stat label="Completed" :value="number_format($stats['completed'])" icon="check-circle" :hint="'Level '.$stats['level']" />
        <x-stat label="Approval rate" :value="$stats['approval_rate'] !== null ? $stats['approval_rate'].'%' : '—'" icon="chart" />
    </div>

    <div class="mt-8 grid gap-6 xl:grid-cols-[1fr_340px]">
        <section>
            <div class="mb-3 flex items-center justify-between">
                <h2 class="flex items-center gap-2 text-sm font-semibold"><span class="size-2 rounded-full bg-secondary"></span> Tasks Available Now</h2>
                <a href="{{ route('developer.tasks.index') }}" class="text-xs text-muted hover:text-ink">View all →</a>
            </div>
            @if ($tasks->isEmpty())
                <div class="card"><x-empty icon="inbox" title="No tasks available right now." text="New tasks are posted throughout the day. Check back soon — we'll keep your spot warm." /></div>
            @else
                <div class="grid gap-3 md:grid-cols-2">
                    @foreach ($tasks as $task)
                        <x-task-card :task="$task" compact />
                    @endforeach
                </div>
            @endif
        </section>

        <aside class="space-y-6">
            <div class="card card-pad">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold">Earnings, last 14 days</h2>
                </div>
                @php $hasEarnings = collect($series)->contains(fn ($d) => $d['amount'] !== '0.00'); @endphp
                @if ($hasEarnings)
                    <div class="mt-3 h-32">
                        <canvas aria-label="Daily approved earnings over the last 14 days" role="img" x-data="chart({
                            type: 'bar',
                            data: { labels: @js(array_map(fn ($d) => \Illuminate\Support\Carbon::parse($d['date'])->format('M j'), $series)),
                                    datasets: [{ label: 'Earnings', data: @js(array_map(fn ($d) => (float) $d['amount'], $series)), backgroundColor: '#16A34A', borderRadius: 4, maxBarThickness: 18 }] },
                            options: { maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => '$' + c.parsed.y.toFixed(2) } } },
                                       scales: { x: { grid: { display: false }, ticks: { maxTicksLimit: 4 } }, y: { beginAtZero: true, ticks: { maxTicksLimit: 3, callback: (v) => '$' + v } } } }
                        })"></canvas>
                    </div>
                @else
                    <x-empty icon="coin" title="No earnings yet" text="Approved tasks will show up here." class="!py-8" />
                @endif
            </div>

            <div class="card">
                <div class="flex items-center justify-between border-b border-line px-5 py-3.5">
                    <h2 class="text-sm font-semibold">Your Recent Activity</h2>
                    <a href="{{ route('developer.submissions.index') }}" class="text-xs text-muted hover:text-ink">All →</a>
                </div>
                @forelse ($activity as $submission)
                    <a href="{{ route('developer.submissions.show', $submission) }}" class="flex items-center justify-between gap-3 border-b border-line/60 px-5 py-3 last:border-0 hover:bg-surface-2/60">
                        <div class="min-w-0">
                            <div class="truncate text-sm">{{ $submission->task->title }}</div>
                            <div class="text-xs text-faint">{{ $submission->submitted_at->diffForHumans() }}</div>
                        </div>
                        <div class="shrink-0 text-right">
                            <div class="text-sm mono-num {{ $submission->status->value === 'approved' ? 'text-secondary' : 'text-ink-2' }}">{{ $submission->reward->format() }}</div>
                            <x-status :value="$submission->status" />
                        </div>
                    </a>
                @empty
                    <x-empty icon="activity" title="No submissions yet" text="Start your first task — it only takes a few minutes." class="!py-10" />
                @endforelse
            </div>
        </aside>
    </div>
</x-layouts.app>
