<x-layouts.app title="Billing">
    <x-page-header title="Billing" subtitle="Fund your balance and track every charge and refund." />
    <div class="grid gap-3 sm:grid-cols-3">
        <x-stat label="Available funds" :value="$wallet->balance->format()" icon="wallet" />
        <x-stat label="Held in escrow" :value="$wallet->pending_balance->format()" icon="lock" hint="Reserved for open task slots" />
        <x-stat label="Total spending" :value="$wallet->lifetime_spending->format()" icon="coin" hint="Approved work incl. fees" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_360px]">
        <section class="card">
            <h2 class="border-b border-line px-5 py-3 text-sm font-semibold">Ledger</h2>
            @if ($transactions->isEmpty())
                <x-empty icon="coin" title="No transactions yet" text="Add funds to get started." />
            @else
                <ul>
                    @foreach ($transactions as $tx)
                        <li class="flex items-center justify-between gap-3 border-b border-line/60 px-5 py-3 last:border-0">
                            <div class="min-w-0">
                                <div class="truncate text-sm">{{ $tx->description }}</div>
                                <div class="text-xs text-faint">{{ $tx->type->label() }} · {{ $tx->created_at->format('M j, H:i') }}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-medium mono-num {{ $tx->amount->isNegative() ? 'text-ink-2' : 'text-secondary' }}">{{ $tx->amount->format(true) }}</div>
                                <div class="text-[11px] text-faint mono-num">bal. {{ $tx->balance_after->format() }}</div>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div class="p-4">{{ $transactions->links() }}</div>
            @endif
        </section>
        <aside class="space-y-6">
            <div class="card card-pad">
                <h2 class="text-sm font-semibold">Add funds</h2>
                <p class="mt-1 text-xs text-muted">Send a bank transfer or PayPal payment, then report it here. Funds are credited once an administrator confirms receipt. Minimum {{ $minimum->format() }}.</p>
                <form method="POST" action="{{ route('requester.deposits.store') }}" class="mt-4 space-y-3">
                    @csrf
                    <x-input name="amount" label="Amount (USD)" inputmode="decimal" value="100.00" />
                    <x-select name="method" label="Method" :options="collect($methods)->mapWithKeys(fn ($m) => [$m->value => $m->label()])->all()" />
                    <x-input name="reference" label="Your payment reference (optional)" placeholder="Bank reference / PayPal transaction ID" />
                    <button class="btn-primary w-full">Report payment</button>
                </form>
            </div>
            <div class="card">
                <h2 class="border-b border-line px-5 py-3 text-sm font-semibold">Deposits</h2>
                @forelse ($deposits as $d)
                    <div class="flex items-center justify-between border-b border-line/60 px-5 py-3 text-sm last:border-0">
                        <div><div class="mono-num">{{ $d->amount->format() }}</div><div class="text-xs text-faint">{{ $d->gateway_reference }} · {{ $d->created_at->format('M j') }}</div></div>
                        <x-status :value="$d->status" />
                    </div>
                @empty
                    <x-empty icon="banknote" title="No deposits yet" class="!py-8" />
                @endforelse
            </div>
        </aside>
    </div>
</x-layouts.app>
