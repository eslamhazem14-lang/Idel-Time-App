<x-layouts.app title="Wallet">
    <x-page-header title="Wallet" subtitle="Balances are derived from an immutable ledger of every credit and debit." />

    <div class="grid gap-3 sm:grid-cols-3">
        <x-stat label="Available balance" :value="$wallet->balance->format()" icon="wallet" accent hint="Withdrawable" />
        <x-stat label="Pending balance" :value="$wallet->pending_balance->format()" icon="clock" hint="Awaiting review" />
        <x-stat label="Lifetime earnings" :value="$wallet->lifetime_earnings->format()" icon="coin" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_360px]">
        <section class="card">
            <div class="flex items-center justify-between border-b border-line px-5 py-3">
                <h2 class="text-sm font-semibold">Transactions</h2>
                <div class="flex rounded-lg border border-line p-0.5 text-xs">
                    <a href="{{ route('developer.wallet') }}" class="rounded-md px-2.5 py-1 {{ $bucket === 'available' ? 'bg-surface-3 text-ink' : 'text-muted' }}">Available</a>
                    <a href="{{ route('developer.wallet', ['ledger' => 'pending']) }}" class="rounded-md px-2.5 py-1 {{ $bucket === 'pending' ? 'bg-surface-3 text-ink' : 'text-muted' }}">Pending</a>
                </div>
            </div>
            @if ($transactions->isEmpty())
                <x-empty icon="coin" title="No earnings yet" text="Complete a task and your rewards will appear here." />
            @else
                <ul>
                    @foreach ($transactions as $tx)
                        <li class="flex items-center justify-between gap-3 border-b border-line/60 px-5 py-3 last:border-0">
                            <div class="min-w-0">
                                <div class="truncate text-sm">{{ $tx->description }}</div>
                                <div class="text-xs text-faint">{{ $tx->type->label() }} · {{ $tx->created_at->format('M j, H:i') }}</div>
                            </div>
                            <div class="shrink-0 text-right">
                                <div class="font-medium mono-num {{ $tx->amount->isNegative() ? 'text-ink-2' : 'text-secondary' }}">{{ $tx->amount->format(true) }}</div>
                                <x-status :value="$tx->status" />
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div class="p-4">{{ $transactions->links() }}</div>
            @endif
        </section>

        <aside class="space-y-6">
            <div class="card card-pad">
                <h2 class="text-sm font-semibold">Request withdrawal</h2>
                <p class="mt-1 text-xs text-muted">Minimum {{ $minimum->format() }}. Paid manually, usually within a few business days.</p>
                @if ($wallet->balance->lessThan($minimum))
                    <div class="mt-4 rounded-lg border border-line bg-surface-2 p-3 text-sm text-muted">
                        You need {{ $minimum->subtract($wallet->balance)->format() }} more to withdraw.
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-surface-3"><div class="h-full rounded-full bg-secondary" style="width: {{ min(100, intdiv($wallet->balance->cents * 100, max(1, $minimum->cents))) }}%"></div></div>
                    </div>
                @else
                    <form method="POST" action="{{ route('developer.withdrawals.store') }}" class="mt-4 space-y-3" x-data="{ method: '{{ old('method', $methods[0]->value ?? '') }}' }">
                        @csrf
                        <x-input name="amount" type="text" inputmode="decimal" label="Amount (USD)" :value="$wallet->balance->toDecimal()" />
                        <div>
                            <label class="label" for="f_method">Method</label>
                            <select id="f_method" name="method" x-model="method" class="field">
                                @foreach ($methods as $m)<option value="{{ $m->value }}">{{ $m->label() }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label" for="f_account">Payout details</label>
                            <textarea id="f_account" name="account_details" rows="3" class="field @error('account_details') field-error @enderror"
                                :placeholder="{ @foreach ($methods as $m)'{{ $m->value }}': @js($m->accountHint()), @endforeach }[method]">{{ old('account_details') }}</textarea>
                            @error('account_details')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                            <p class="mt-1 text-[11px] text-faint"><x-icon name="lock" class="inline size-3" /> Stored encrypted. Only administrators processing your payout can see it.</p>
                        </div>
                        <button class="btn-primary w-full">Request withdrawal</button>
                    </form>
                @endif
            </div>

            <div class="card">
                <h2 class="border-b border-line px-5 py-3 text-sm font-semibold">Withdrawals</h2>
                @forelse ($withdrawals as $w)
                    <div class="flex items-center justify-between border-b border-line/60 px-5 py-3 text-sm last:border-0">
                        <div>
                            <div class="mono-num">{{ $w->amount->format() }}</div>
                            <div class="text-xs text-faint">{{ $w->method->label() }} · {{ $w->created_at->format('M j') }}</div>
                            @if ($w->admin_note && $w->status->value === 'rejected')<div class="text-xs text-danger">{{ $w->admin_note }}</div>@endif
                        </div>
                        <x-status :value="$w->status" />
                    </div>
                @empty
                    <x-empty icon="banknote" title="No withdrawals" text="Your withdrawal requests will appear here." class="!py-8" />
                @endforelse
            </div>
        </aside>
    </div>
</x-layouts.app>
