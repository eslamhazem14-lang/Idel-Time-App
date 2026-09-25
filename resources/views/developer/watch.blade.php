<x-layouts.app title="Watch & earn">
    <div x-data="watchEarn(@js([
            'provider' => $provider,
            'adUnitPath' => $adUnitPath,
            'minSeconds' => $minSeconds,
            'state' => $state,
            'urls' => [
                'status' => route('developer.watch.status'),
                'start' => route('developer.watch.start'),
                'complete' => route('developer.watch.complete', '__VIEW__'),
            ],
        ]))">
        <x-page-header title="Watch & earn" subtitle="Watch short sponsored videos while your AI agent works. You get {{ rtrim(rtrim($sharePercent, '0'), '.') }}% of the ad revenue they bring in." >
            <a href="{{ route('developer.tasks.index') }}" class="btn-secondary"><x-icon name="search" /> Find a task instead</a>
        </x-page-header>

        {{-- Agent status --}}
        <div class="mb-6 flex flex-col gap-3 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between"
             :class="state.agent.working ? 'border-primary/40 bg-primary/10' : 'border-line bg-surface'">
            <div class="flex items-center gap-3">
                <span class="relative grid size-10 place-items-center rounded-lg" :class="state.agent.working ? 'bg-primary/20 text-primary' : 'bg-surface-3 text-muted'">
                    <x-icon name="terminal" class="size-5" />
                    <span x-show="state.agent.working" class="absolute -right-0.5 -top-0.5 size-2.5 animate-pulse rounded-full bg-secondary"></span>
                </span>
                <div>
                    <div class="font-medium" x-text="state.agent.working ? 'Claude is working…' : 'No agent running'"></div>
                    <div class="text-xs text-muted" x-show="state.agent.working">Keep watching — we'll tell you when it's done.</div>
                    <div class="text-xs text-muted" x-show="!state.agent.working">
                        <a href="{{ route('developer.connect') }}" class="underline hover:text-ink">Connect Claude Code</a> to open this page automatically when your agent starts.
                    </div>
                </div>
            </div>
            <button type="button" class="btn-ghost btn-sm" @click="askNotify()" x-show="'Notification' in window && Notification.permission === 'default'">
                <x-icon name="bell" /> Notify me when it's done
            </button>
        </div>

        <div x-show="finishedNotice" x-cloak class="mb-6 flex items-center justify-between gap-3 rounded-xl border border-secondary/40 bg-secondary/10 p-4 text-sm text-secondary">
            <span class="flex items-center gap-2"><x-icon name="check-circle" class="size-5" /> Claude finished. Switch back to your terminal.</span>
            <button class="btn-ghost btn-sm" @click="finishedNotice = false" aria-label="Dismiss"><x-icon name="x" /></button>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
            {{-- Player --}}
            <section class="card overflow-hidden">
                <div class="relative grid aspect-video place-items-center bg-bg">
                    <template x-if="phase === 'playing' && '{{ $provider }}' === 'demo'">
                        <div class="absolute inset-0 grid place-items-center bg-gradient-to-br from-primary/25 via-bg to-secondary/10">
                            <div class="text-center">
                                <div class="text-[10px] font-semibold uppercase tracking-[0.2em] text-faint">Sponsored · demo ad</div>
                                <div class="mt-2 text-2xl font-semibold">Your product could be here</div>
                                <div class="mt-1 text-sm text-muted">Placeholder. Real ads play once an ad network is connected.</div>
                            </div>
                        </div>
                    </template>
                    <div x-show="phase === 'playing' && '{{ $provider }}' !== 'demo'" class="text-sm text-muted">The ad is playing in the overlay…</div>
                    <div x-show="phase === 'loading' || phase === 'verifying'" class="text-sm text-muted" x-text="phase === 'loading' ? 'Loading ad…' : 'Checking…'"></div>
                    <div x-show="['idle', 'done', 'error'].includes(phase)" class="px-6 text-center">
                        <div class="mx-auto grid size-14 place-items-center rounded-full bg-primary/15 text-primary"><x-icon name="play" class="size-6" /></div>
                        <p x-show="message" x-text="message" class="mt-3 text-sm" :class="phase === 'error' ? 'text-danger' : 'text-secondary'"></p>
                        <button type="button" class="btn-primary mt-4" @click="watch()" :disabled="!canWatch">
                            <span x-text="phase === 'idle' ? 'Watch an ad' : 'Watch another'"></span>
                        </button>
                        <p class="mt-2 text-xs text-faint" x-show="state.today >= state.cap">Daily limit reached. Come back tomorrow.</p>
                        <p class="mt-2 text-xs text-faint" x-show="!state.enabled">Watch & earn is turned off right now.</p>
                    </div>
                    <div x-show="phase === 'playing'" class="absolute inset-x-0 bottom-0 h-1 bg-surface-3">
                        <div class="h-full bg-secondary transition-[width]" :style="`width: ${progress}%`"></div>
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2 border-t border-line px-5 py-3 text-xs text-muted">
                    <span>Watch each ad to the end (at least {{ $minSeconds }}s) for it to count.</span>
                    <span class="mono-num"><span x-text="state.today"></span> / <span x-text="state.cap"></span> today</span>
                </div>
                @unless ($countsViews)
                    <div class="border-t border-amber/30 bg-amber/10 px-5 py-2 text-xs text-amber">Demo mode: ads on this server are placeholders and are not counted towards earnings.</div>
                @endunless
            </section>

            <aside class="space-y-4">
                <div class="card card-pad">
                    <div class="text-xs font-medium text-muted">Ads awaiting payout</div>
                    <div class="mt-2 text-2xl font-semibold mono-num" x-text="state.awaiting">{{ $state['awaiting'] }}</div>
                </div>
                <div class="card card-pad">
                    <div class="text-xs font-medium text-muted">Estimated share</div>
                    <div class="mt-2 text-2xl font-semibold mono-num" x-text="'≈ ' + state.estimate">≈ {{ $state['estimate'] }}</div>
                    <div class="mt-1 text-xs text-faint">An estimate. The real amount depends on what advertisers paid.</div>
                </div>
                <div class="card card-pad">
                    <div class="text-xs font-medium text-muted">Paid to your wallet from ads</div>
                    <div class="mt-2 text-2xl font-semibold mono-num text-secondary" x-text="state.paid_out">{{ $state['paid_out'] }}</div>
                </div>
                <div class="card card-pad text-sm text-muted">
                    <h2 class="mb-2 text-sm font-semibold text-ink">How ad earnings work</h2>
                    <ol class="list-decimal space-y-1.5 pl-4 text-xs">
                        <li>Each ad you watch to the end is counted.</li>
                        <li>The ad network pays us for the ads, usually once a month.</li>
                        <li>We split {{ rtrim(rtrim($sharePercent, '0'), '.') }}% of that payment between developers, by the number of ads each one watched.</li>
                        <li>Your share lands in your wallet's available balance, ready to withdraw.</li>
                    </ol>
                </div>
            </aside>
        </div>

        @if ($payouts->isNotEmpty())
            <section class="card mt-6">
                <h2 class="border-b border-line px-5 py-3 text-sm font-semibold">Recent ad payouts</h2>
                <ul>
                    @foreach ($payouts as $row)
                        <li class="flex items-center justify-between border-b border-line/60 px-5 py-3 text-sm last:border-0">
                            <span>{{ $row->payout?->period_start->format('M j') }} – {{ $row->payout?->period_end->format('M j, Y') }}</span>
                            <span class="text-muted">{{ $row->views }} ads</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-layouts.app>
