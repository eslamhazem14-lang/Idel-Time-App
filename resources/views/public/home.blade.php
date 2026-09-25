<x-layouts.public>
    {{-- Hero --}}
    <section class="relative overflow-hidden border-b border-line grid-bg">
        <div class="mx-auto grid max-w-6xl items-center gap-12 px-4 py-16 sm:px-6 lg:grid-cols-[1.05fr_1fr] lg:py-20">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full border border-line-strong bg-surface px-3 py-1 text-xs text-ink-2">
                    <span class="size-1.5 rounded-full bg-secondary"></span> Your AI can work. So can you.
                </span>
                <h1 class="mt-5 text-4xl font-semibold tracking-tight text-ink sm:text-5xl">Turn AI Waiting Time Into Money.</h1>
                <p class="mt-4 max-w-lg text-lg text-muted">Complete small technical tasks while your AI coding agent works. Code reviews, AI evaluations, website QA — sized for the minutes you already have.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('register', ['as' => 'developer']) }}" class="btn-primary btn-lg">Start Earning <x-icon name="arrow-right" class="size-4" /></a>
                    <a href="{{ route('register', ['as' => 'requester']) }}" class="btn-secondary btn-lg">Post Tasks</a>
                </div>
                <p class="mt-4 text-xs text-faint">Earnings depend on task availability, qualification, and approval.</p>
            </div>

            {{-- Product illustration: agent running + tasks that fit --}}
            <div class="card overflow-hidden shadow-2xl shadow-black/50">
                <div class="flex items-center gap-1.5 border-b border-line bg-surface-2 px-4 py-2.5">
                    <span class="size-2.5 rounded-full bg-white/10"></span><span class="size-2.5 rounded-full bg-white/10"></span><span class="size-2.5 rounded-full bg-white/10"></span>
                    <span class="ml-3 font-mono text-[11px] text-faint">~/projects/api — agent</span>
                </div>
                <div class="border-b border-line bg-bg px-4 py-3 font-mono text-xs leading-6 text-ink-2">
                    <div><span class="text-secondary">❯</span> refactor the auth module and add tests</div>
                    <div class="text-muted">● Reading 14 files… planning changes</div>
                    <div class="flex items-center gap-2 text-primary"><span class="inline-block size-1.5 animate-pulse rounded-full bg-primary"></span> Working · est. 12 min</div>
                </div>
                <div class="p-4">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-semibold">AI is working. Earn while you wait.</div>
                        <x-badge color="violet">12 min free</x-badge>
                    </div>
                    <ul class="mt-3 space-y-2">
                        @foreach ([['Review an AI-generated answer', 3, '0.35'], ['Review a code snippet', 5, '0.50'], ['Verify documentation', 4, '0.40'], ['Test a website', 7, '0.75']] as [$t, $m, $r])
                            <li class="flex items-center justify-between rounded-lg border border-line bg-surface-2/60 px-3 py-2.5">
                                <div>
                                    <div class="text-sm text-ink">{{ $t }}</div>
                                    <div class="text-[11px] text-faint">{{ $m }} min · {{ \App\Support\Money::of($r)->perMinute($m) }}</div>
                                </div>
                                <span class="font-semibold text-secondary mono-num">${{ $r }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
        <div class="max-w-2xl">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-primary">How it works</p>
            <h2 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">Open app → pick a task → complete → earn.</h2>
            <p class="mt-3 text-muted">No searching, applying or negotiating. Every task is small, self-contained and ready to start now.</p>
        </div>
        <div class="mt-10">@include('public._steps')</div>
    </section>

    {{-- Example tasks --}}
    <section class="border-y border-line bg-surface/40">
        <div class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-primary">Example tasks</p>
                    <h2 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">Technical work that needs a human.</h2>
                </div>
                <a href="{{ route('developers') }}" class="btn-ghost">For developers <x-icon name="arrow-right" class="size-4" /></a>
            </div>
            <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['sparkles', 'AI Evaluation', 'Rate a model\'s answer to a coding question for correctness, relevance and safety.', 3, '0.35'],
                    ['code', 'Code Review', 'Read a 40-line diff and answer three targeted questions about edge cases.', 5, '0.50'],
                    ['globe', 'Website QA', 'Walk through a checkout flow on mobile and report what breaks.', 7, '0.75'],
                    ['book', 'Documentation', 'Check five claims in an API reference against the real behavior.', 4, '0.40'],
                    ['bug', 'Bug Reproduction', 'Follow reproduction steps in a fresh environment and report the outcome.', 8, '0.90'],
                    ['database', 'Data Validation', 'Verify that 10 extracted records match their source documents.', 6, '0.55'],
                ] as [$icon, $cat, $text, $min, $reward])
                    <div class="card card-pad">
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2 text-xs font-medium text-muted"><x-icon :name="$icon" class="size-4 text-primary" /> {{ $cat }}</span>
                            <span class="font-semibold text-secondary mono-num">${{ $reward }}</span>
                        </div>
                        <p class="mt-3 text-sm text-ink-2">{{ $text }}</p>
                        <div class="mt-4 flex gap-2"><x-badge color="violet">{{ $min }} min</x-badge><x-badge>{{ \App\Support\Money::of($reward)->perMinute($min) }}</x-badge></div>
                    </div>
                @endforeach
            </div>
            <p class="mt-4 text-xs text-faint">Illustrative examples. Actual tasks and rewards are set by requesters.</p>
        </div>
    </section>

    {{-- Benefits --}}
    <section class="mx-auto grid max-w-6xl gap-6 px-4 py-20 sm:px-6 lg:grid-cols-2">
        <div class="card p-6 sm:p-8">
            <x-icon name="terminal" class="size-5 text-primary" />
            <h2 class="mt-4 text-xl font-semibold tracking-tight">For developers</h2>
            <p class="mt-2 text-sm text-muted">Minutes matter. Use the gaps between prompts.</p>
            <ul class="mt-6 space-y-3 text-sm">
                @foreach (['Tasks sized from 2 to 15 minutes', 'Reward per minute shown up front', 'Server-side timer — no surprises', 'Wallet with manual bank or PayPal withdrawals', 'Appeal any rejection you think is unfair'] as $b)
                    <li class="flex gap-3 text-ink-2"><x-icon name="check" class="mt-0.5 size-4 text-secondary" /> {{ $b }}</li>
                @endforeach
            </ul>
            <a href="{{ route('register', ['as' => 'developer']) }}" class="btn-primary mt-8">Start Earning</a>
        </div>
        <div class="card p-6 sm:p-8">
            <x-icon name="layers" class="size-5 text-primary" />
            <h2 class="mt-4 text-xl font-semibold tracking-tight">For task requesters</h2>
            <p class="mt-2 text-sm text-muted">Human technical validation on demand, at microtask granularity.</p>
            <ul class="mt-6 space-y-3 text-sm">
                @foreach (['Reach developers, not generic crowd workers', 'Batch hundreds of similar items at once', 'Budget held in escrow — pay only for approved work', 'Approve, reject or request a revision', 'Completion, approval and cost analytics'] as $b)
                    <li class="flex gap-3 text-ink-2"><x-icon name="check" class="mt-0.5 size-4 text-secondary" /> {{ $b }}</li>
                @endforeach
            </ul>
            <a href="{{ route('register', ['as' => 'requester']) }}" class="btn-secondary mt-8">Post a Task</a>
        </div>
    </section>

    {{-- Earnings --}}
    <section class="border-y border-line bg-surface/40">
        <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 py-20 sm:px-6 lg:grid-cols-2">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-primary">Earnings</p>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">Transparent, per task.</h2>
                <p class="mt-3 text-muted">You see the exact reward before you start. Developers keep 100% of it; the platform commission is paid by the requester on top.</p>
                <p class="mt-3 text-sm text-faint">We don't publish income estimates: earnings depend on task availability, your qualification, and approval of your work.</p>
            </div>
            <div class="card overflow-hidden">
                <dl class="divide-y divide-line text-sm">
                    <div class="flex justify-between px-5 py-3.5"><dt class="text-muted">Developer reward</dt><dd class="font-semibold text-secondary mono-num">{{ $example->reward->format() }}</dd></div>
                    <div class="flex justify-between px-5 py-3.5"><dt class="text-muted">Platform fee ({{ rtrim(rtrim($example->commissionPercent, '0'), '.') }}%)</dt><dd class="mono-num">{{ $example->platformFee->format() }}</dd></div>
                    <div class="flex justify-between bg-surface-2 px-5 py-3.5"><dt class="font-medium">Requester pays</dt><dd class="font-semibold mono-num">{{ $example->unitCost()->format() }}</dd></div>
                </dl>
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="mx-auto max-w-3xl px-4 py-20 sm:px-6">
        <h2 class="text-2xl font-semibold tracking-tight">Frequently asked questions</h2>
        <div class="mt-8">@include('public._faq-list', ['faqs' => array_slice(\App\Http\Controllers\PageController::faqs(), 0, 5)])</div>
        <a href="{{ route('faq') }}" class="btn-ghost mt-4">All questions <x-icon name="arrow-right" class="size-4" /></a>
    </section>
</x-layouts.public>
