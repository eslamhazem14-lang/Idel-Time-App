<x-layouts.app title="Admin overview">
    <x-page-header title="Platform overview" subtitle="Last 30 days unless stated otherwise." />

    {{-- Action queue --}}
    <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        @foreach ([
            ['admin.tasks.index', 'Tasks to approve', $cards['pending_tasks'], 'layers'],
            ['admin.submissions.index', 'Pending submissions', $cards['pending_submissions'], 'inbox', ['status' => 'pending']],
            ['admin.withdrawals.index', 'Pending withdrawals', $cards['pending_withdrawals'], 'banknote'],
            ['admin.disputes.index', 'Open disputes', $cards['open_disputes'], 'scale'],
            ['admin.fraud.index', 'Fraud signals', $cards['open_flags'], 'shield'],
        ] as $item)
            @php [$route, $label, $count, $icon] = $item; $params = $item[4] ?? []; @endphp
            <a href="{{ route($route, $params) }}" class="card flex items-center justify-between p-4 transition hover:border-line-strong {{ $count ? '' : 'opacity-70' }}">
                <div>
                    <div class="text-xs text-muted">{{ $label }}</div>
                    <div class="mt-1 text-xl font-semibold mono-num {{ $count ? 'text-amber' : '' }}">{{ $count }}</div>
                </div>
                <x-icon :name="$icon" class="size-5 text-faint" />
            </a>
        @endforeach
    </div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 xl:grid-cols-7">
        <x-stat label="Total developers" :value="number_format($cards['developers'])" />
        <x-stat label="Active developers" :value="number_format($cards['active_developers'])" hint="Submitted in 30 days" />
        <x-stat label="Total requesters" :value="number_format($cards['requesters'])" />
        <x-stat label="Active tasks" :value="number_format($cards['active_tasks'])" />
        <x-stat label="Completed today" :value="number_format($cards['completed_today'])" />
        <x-stat label="Rewards paid" :value="$cards['rewards_paid']->format()" hint="All time" />
        <x-stat label="Platform revenue" :value="$cards['platform_revenue']->format()" hint="Commission balance" accent />
    </div>

    @php
        $shortLabels = array_map(fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('M j'), $charts['labels']);
        $axis = "{ x: { grid: { display: false }, ticks: { maxTicksLimit: 6 } }, y: { beginAtZero: true, grid: { color: '#1D232D' }, ticks: { maxTicksLimit: 4 } } }";
    @endphp
    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <div class="card card-pad">
            <h2 class="text-sm font-semibold">Tasks completed over time</h2>
            <p class="text-xs text-muted">Approved submissions per day</p>
            <div class="mt-4 h-56">
                <canvas role="img" aria-label="Approved submissions per day, last 30 days" x-data="chart({
                    type: 'line',
                    data: { labels: @js($shortLabels), datasets: [{ label: 'Completed', data: @js($charts['completed']), borderColor: '#7C5CFC', backgroundColor: '#7C5CFC22', fill: true, borderWidth: 2, pointRadius: 0, pointHoverRadius: 4, cubicInterpolationMode: 'monotone' }] },
                    options: { maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, plugins: { legend: { display: false } }, scales: {{ $axis }} }
                })"></canvas>
            </div>
        </div>
        <div class="card card-pad">
            <h2 class="text-sm font-semibold">Platform revenue vs developer earnings</h2>
            <p class="text-xs text-muted">USD per day</p>
            <div class="mt-4 h-56">
                <canvas role="img" aria-label="Daily platform revenue and developer earnings, last 30 days" x-data="chart({
                    type: 'line',
                    data: { labels: @js($shortLabels), datasets: [
                        { label: 'Developer earnings', data: @js($charts['earnings']), borderColor: '#16A34A', backgroundColor: '#16A34A', borderWidth: 2, pointRadius: 0, pointHoverRadius: 4, cubicInterpolationMode: 'monotone' },
                        { label: 'Platform revenue', data: @js($charts['revenue']), borderColor: '#7C5CFC', backgroundColor: '#7C5CFC', borderWidth: 2, pointRadius: 0, pointHoverRadius: 4, cubicInterpolationMode: 'monotone' }
                    ] },
                    options: { maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                        plugins: { legend: { position: 'top', align: 'end' }, tooltip: { callbacks: { label: (c) => c.dataset.label + ': $' + c.parsed.y.toFixed(2) } } },
                        scales: {{ $axis }} }
                })"></canvas>
            </div>
        </div>
        <div class="card card-pad">
            <h2 class="text-sm font-semibold">Tasks by category</h2>
            <div class="mt-4 h-64">
                <canvas role="img" aria-label="Number of tasks per category" x-data="chart({
                    type: 'bar',
                    data: { labels: @js($charts['categories']['labels']), datasets: [{ label: 'Tasks', data: @js($charts['categories']['data']), backgroundColor: '#7C5CFC', borderRadius: 4, maxBarThickness: 16 }] },
                    options: { indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, grid: { color: '#1D232D' }, ticks: { precision: 0 } }, y: { grid: { display: false } } } }
                })"></canvas>
            </div>
        </div>
        <div class="card card-pad">
            <h2 class="text-sm font-semibold">Review outcomes</h2>
            <p class="text-xs text-muted">Submissions reviewed in the last 30 days</p>
            @php
                $r = $charts['review']; $totalReviews = array_sum($r);
                $pct = fn ($n) => $totalReviews ? round($n * 100 / $totalReviews) : 0;
            @endphp
            @if ($totalReviews)
                <div class="mt-6 flex h-3 gap-0.5 overflow-hidden rounded-full" role="img" aria-label="Approved {{ $r['approved'] }}, rejected {{ $r['rejected'] }}, revision requested {{ $r['revision'] }}">
                    <div class="bg-secondary" style="width: {{ $pct($r['approved']) }}%"></div>
                    <div class="bg-danger" style="width: {{ $pct($r['rejected']) }}%"></div>
                    <div class="bg-amber" style="width: {{ $pct($r['revision']) }}%"></div>
                </div>
                <dl class="mt-5 grid grid-cols-3 gap-3 text-sm">
                    <div><dt class="flex items-center gap-1.5 text-xs text-muted"><x-icon name="check" class="size-3.5 text-secondary" /> Approved</dt><dd class="mt-1 text-lg font-semibold mono-num">{{ $r['approved'] }} <span class="text-xs font-normal text-faint">{{ $pct($r['approved']) }}%</span></dd></div>
                    <div><dt class="flex items-center gap-1.5 text-xs text-muted"><x-icon name="x" class="size-3.5 text-danger" /> Rejected</dt><dd class="mt-1 text-lg font-semibold mono-num">{{ $r['rejected'] }} <span class="text-xs font-normal text-faint">{{ $pct($r['rejected']) }}%</span></dd></div>
                    <div><dt class="flex items-center gap-1.5 text-xs text-muted"><x-icon name="edit" class="size-3.5 text-amber" /> Revision</dt><dd class="mt-1 text-lg font-semibold mono-num">{{ $r['revision'] }} <span class="text-xs font-normal text-faint">{{ $pct($r['revision']) }}%</span></dd></div>
                </dl>
            @else
                <x-empty icon="chart" title="No reviews yet" class="!py-8" />
            @endif
        </div>
    </div>

    <div class="card mt-6">
        <div class="flex items-center justify-between border-b border-line px-5 py-3.5">
            <h2 class="text-sm font-semibold">Recent activity</h2>
            <a href="{{ route('admin.activity.index') }}" class="text-xs text-muted hover:text-ink">Full log →</a>
        </div>
        @include('admin._activity-rows', ['logs' => $activity])
    </div>
</x-layouts.app>
