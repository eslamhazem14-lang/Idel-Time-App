<x-layouts.app title="Ad revenue">
    <x-page-header title="Ad revenue" subtitle="When the ad network pays you, record it here. The developers' share is split by the ads each one watched." />

    @unless ($countsViews)
        <div class="mb-6 rounded-lg border border-amber/30 bg-amber/10 p-3 text-sm text-amber">
            The ad provider is <strong>demo</strong> and demo views are not counted on this server. Set <code>AD_PROVIDER=google_rewarded</code> and <code>GOOGLE_AD_UNIT_PATH</code> to serve real ads.
        </div>
    @endunless

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat label="Provider" :value="$provider" icon="play" />
        <x-stat label="Developer share" :value="rtrim(rtrim($sharePercent, '0'), '.').'%'" icon="users" />
        <x-stat label="Ads watched today" :value="number_format($today)" icon="eye" />
        <x-stat label="Awaiting payout" :value="number_format($awaiting->sum('views'))" icon="clock" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_380px]">
        <div class="space-y-6">
            <section class="card overflow-x-auto">
                <h2 class="border-b border-line px-5 py-3 text-sm font-semibold">Watched ads awaiting payout</h2>
                @if ($awaiting->isEmpty())
                    <x-empty icon="play" title="Nothing awaiting payout" text="Ads developers watch to the end will show up here, grouped by month." />
                @else
                    <table class="table">
                        <thead><tr><th>Month</th><th>Ads</th><th>Developers</th><th>From</th><th>To</th></tr></thead>
                        <tbody>
                            @foreach ($awaiting as $row)
                                <tr>
                                    <td class="font-medium">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $row->month)->format('F Y') }}</td>
                                    <td class="mono-num">{{ number_format($row->views) }}</td>
                                    <td class="mono-num">{{ $row->developers }}</td>
                                    <td class="text-muted">{{ \Illuminate\Support\Carbon::parse($row->first_at)->format('M j') }}</td>
                                    <td class="text-muted">{{ \Illuminate\Support\Carbon::parse($row->last_at)->format('M j') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>

            <section class="card overflow-x-auto">
                <h2 class="border-b border-line px-5 py-3 text-sm font-semibold">Payout history</h2>
                @if ($payouts->isEmpty())
                    <x-empty icon="banknote" title="No payouts recorded yet" text="Record the first payment from your ad network to share it with developers." />
                @else
                    <table class="table">
                        <thead><tr><th>Period</th><th>Received</th><th>Paid to developers</th><th>Ads</th><th>Developers</th><th>Reference</th></tr></thead>
                        <tbody>
                            @foreach ($payouts as $p)
                                <tr>
                                    <td>{{ $p->period_start->format('M j') }} – {{ $p->period_end->format('M j, Y') }}</td>
                                    <td class="mono-num">{{ $p->gross_amount->format() }}</td>
                                    <td class="mono-num text-secondary">{{ $p->distributed_amount->format() }} <span class="text-xs text-faint">({{ rtrim(rtrim($p->share_percent, '0'), '.') }}%)</span></td>
                                    <td class="mono-num">{{ number_format($p->view_count) }}</td>
                                    <td class="mono-num">{{ $p->developer_count }}</td>
                                    <td class="text-muted">{{ $p->reference ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="p-4">{{ $payouts->links() }}</div>
                @endif
            </section>
        </div>

        <aside class="space-y-6">
            <form method="GET" action="{{ route('admin.ads.index') }}" class="card card-pad space-y-4">
                <h2 class="text-sm font-semibold">Record an ad network payment</h2>
                <p class="text-xs text-muted">Use the dates your ad network's payment covers (Google pays each month's earnings around the 21st of the next month). Enter the amount you actually received.</p>
                <div class="grid grid-cols-2 gap-3">
                    <x-input name="period_start" type="date" label="From" :value="request('period_start')" />
                    <x-input name="period_end" type="date" label="To" :value="request('period_end')" />
                </div>
                <x-input name="gross_amount" label="Amount received ($)" inputmode="decimal" :value="request('gross_amount')" />
                <button class="btn-secondary w-full">Preview split</button>
            </form>

            @if ($preview)
                <form method="POST" action="{{ route('admin.ads.distribute') }}" class="card card-pad space-y-4"
                      onsubmit="return confirm('Pay developers now? This cannot be undone.')">
                    @csrf
                    <input type="hidden" name="period_start" value="{{ request('period_start') }}">
                    <input type="hidden" name="period_end" value="{{ request('period_end') }}">
                    <input type="hidden" name="gross_amount" value="{{ request('gross_amount') }}">
                    <h2 class="text-sm font-semibold">Preview</h2>
                    <dl class="grid grid-cols-2 gap-2 text-sm">
                        <dt class="text-muted">Ads in period</dt><dd class="text-right mono-num">{{ number_format($preview['views']) }}</dd>
                        <dt class="text-muted">Developers</dt><dd class="text-right mono-num">{{ $preview['developers'] }}</dd>
                        <dt class="text-muted">Developer pool</dt><dd class="text-right mono-num text-secondary">{{ $preview['pool']->format() }}</dd>
                    </dl>
                    @if ($preview['views'] === 0)
                        <p class="text-sm text-amber">No watched ads are awaiting payout in this period.</p>
                    @else
                        <ul class="max-h-60 overflow-y-auto rounded-lg border border-line text-sm">
                            @foreach ($preview['shares'] as $userId => $share)
                                <li class="flex justify-between border-b border-line/60 px-3 py-2 last:border-0">
                                    <span class="truncate">{{ $preview['users'][$userId] ?? '#'.$userId }} <span class="text-xs text-faint">· {{ $share['views'] }} ads</span></span>
                                    <span class="mono-num">{{ $share['amount']->format() }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <x-input name="reference" label="Payment reference (optional)" placeholder="Google payment ID" />
                        <x-textarea name="note" label="Note (optional)" rows="2" />
                        <button class="btn-primary w-full">Pay developers</button>
                    @endif
                </form>
            @endif
        </aside>
    </div>
</x-layouts.app>
