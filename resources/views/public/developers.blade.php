<x-layouts.public title="For developers" description="Short, technical, paid tasks for developers who use AI coding tools. Flexible, no commitment, withdraw to bank or PayPal.">
    <section class="mx-auto max-w-6xl px-4 pt-16 sm:px-6">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-primary">For developers</p>
        <h1 class="mt-2 max-w-3xl text-3xl font-semibold tracking-tight sm:text-4xl">Your AI can work. So can you.</h1>
        <p class="mt-3 max-w-2xl text-muted">Complete small technical tasks while your coding agent runs. No profile to polish, no proposals to write.</p>
        <div class="mt-6 flex gap-3"><a href="{{ route('register', ['as' => 'developer']) }}" class="btn-primary btn-lg">Start Earning</a></div>
    </section>
    <section class="mx-auto mt-14 grid max-w-6xl gap-4 px-4 sm:grid-cols-2 sm:px-6 lg:grid-cols-3">
        @foreach ([
            ['clock', 'Short tasks', 'Every task is estimated between 2 and 15 minutes, so it fits inside a typical agent run.'],
            ['refresh', 'Flexible work', 'Work whenever you like. There are no schedules, quotas or minimum hours.'],
            ['x', 'No long-term commitment', 'Each task stands alone. Pick one up, finish it, and you are done.'],
            ['code', 'Technical tasks', 'Code review, AI answer evaluation, website QA, documentation checks, bug reproduction.'],
            ['wallet', 'Wallet', 'Track pending and available balances, and every ledger entry behind them.'],
            ['banknote', 'Withdrawal', 'Withdraw from '.settings()->money('min_withdrawal')->format().' via bank transfer, PayPal or another manual method.'],
        ] as [$icon, $title, $text])
            <div class="card card-pad">
                <x-icon :name="$icon" class="size-5 text-primary" />
                <h2 class="mt-3 font-semibold">{{ $title }}</h2>
                <p class="mt-1 text-sm text-muted">{{ $text }}</p>
            </div>
        @endforeach
    </section>
    <section class="mx-auto mt-14 max-w-6xl px-4 sm:px-6">
        <div class="card p-6">
            <h2 class="font-semibold">Pick by reward per minute</h2>
            <p class="mt-1 text-sm text-muted">Every task shows its reward divided by its estimated time — e.g. $0.50 / 5 min = <span class="text-ink mono-num">$0.100/min</span> — so you can choose what's worth your minutes.</p>
            <p class="mt-4 text-xs text-faint">Earnings depend on task availability, qualification, and approval.</p>
        </div>
    </section>
</x-layouts.public>
