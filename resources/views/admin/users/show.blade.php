<x-layouts.app :title="$user->name">
    <x-page-header :title="$user->name" :subtitle="$user->email.' · '.$user->role->label().' · joined '.$user->created_at->format('M j, Y')" :back="route('admin.users.index')">
        <x-status :value="$user->status" />
        @unless ($user->isAdmin())
            @if ($user->isSuspended())
                <form method="POST" action="{{ route('admin.users.reactivate', $user) }}">@csrf<button class="btn-secondary">Reactivate</button></form>
            @else
                <button class="btn-danger" @click="$dispatch('open-modal', 'suspend')">Suspend</button>
            @endif
            <button class="btn-secondary" @click="$dispatch('open-modal', 'adjust')"><x-icon name="wallet" /> Adjust wallet</button>
        @endunless
    </x-page-header>

    @if ($user->isSuspended())
        <div class="mb-6 rounded-lg border border-danger/30 bg-danger/10 p-4 text-sm">Suspended {{ $user->suspended_at?->diffForHumans() }}: {{ $user->suspension_reason }}</div>
    @endif

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
        <x-stat label="Available" :value="$wallet->balance->format()" />
        <x-stat :label="$user->isRequester() ? 'Escrow' : 'Pending'" :value="$wallet->pending_balance->format()" />
        <x-stat label="Lifetime earnings" :value="$wallet->lifetime_earnings->format()" />
        <x-stat label="Lifetime spending" :value="$wallet->lifetime_spending->format()" />
        <x-stat label="Fraud score" :value="$user->fraud_score" :hint="$user->isFlagged() ? 'Needs review' : 'Below review threshold'" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <div class="card">
            <h2 class="border-b border-line px-5 py-3 text-sm font-semibold">Fraud signals</h2>
            @forelse ($user->fraudFlags->sortByDesc('id') as $flag)
                <div class="flex items-start justify-between gap-3 border-b border-line/60 px-5 py-3 text-sm last:border-0">
                    <div>
                        <div class="font-medium">{{ $flag->type->label() }} <span class="text-xs text-faint">+{{ $flag->score }}</span></div>
                        <div class="font-mono text-[11px] text-faint">{{ json_encode($flag->details) }}</div>
                        <div class="text-xs text-faint">{{ $flag->created_at->diffForHumans() }}</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-status :value="$flag->status" />
                        @if ($flag->status->value === 'open')
                            <form method="POST" action="{{ route('admin.fraud.update', $flag) }}">@csrf<input type="hidden" name="status" value="dismissed"><button class="btn-ghost btn-sm">Dismiss</button></form>
                            <form method="POST" action="{{ route('admin.fraud.update', $flag) }}">@csrf<input type="hidden" name="status" value="confirmed"><button class="btn-danger btn-sm">Confirm</button></form>
                        @endif
                    </div>
                </div>
            @empty
                <x-empty icon="shield" title="No fraud signals" class="!py-8" />
            @endforelse
        </div>
        <div class="card card-pad text-sm">
            <h2 class="mb-3 font-semibold">Account</h2>
            <dl class="grid grid-cols-2 gap-y-2">
                <dt class="text-muted">Email verified</dt><dd>{{ $user->email_verified_at?->format('M j, Y') ?? 'No' }}</dd>
                <dt class="text-muted">Last login</dt><dd>{{ $user->last_login_at?->diffForHumans() ?? '—' }}</dd>
                <dt class="text-muted">Registration IP</dt><dd class="font-mono text-xs">{{ $user->registration_ip ?? '—' }}</dd>
                <dt class="text-muted">Last login IP</dt><dd class="font-mono text-xs">{{ $user->last_login_ip ?? '—' }}</dd>
                <dt class="text-muted">Country / TZ</dt><dd>{{ $user->country ?? '—' }} / {{ $user->timezone }}</dd>
                @if ($user->isDeveloper())
                    <dt class="text-muted">Level</dt><dd>{{ $user->developer_level }}</dd>
                    <dt class="text-muted">Approval rate</dt><dd>{{ $stats['approval_rate'] ?? '—' }}{{ isset($stats['approval_rate']) ? '%' : '' }}</dd>
                    <dt class="text-muted">GitHub</dt><dd class="truncate">{{ $user->developerProfile?->github_url ?? '—' }}</dd>
                @endif
            </dl>
            @if ($sameIp->isNotEmpty())
                <h3 class="mt-5 text-xs font-semibold uppercase tracking-wider text-muted">Accounts sharing an IP</h3>
                <ul class="mt-2 space-y-1">
                    @foreach ($sameIp as $other)<li><a href="{{ route('admin.users.show', $other) }}" class="hover:text-primary">{{ $other->name }}</a> <span class="text-xs text-faint">{{ $other->email }}</span></li>@endforeach
                </ul>
            @endif
        </div>
        <div class="card">
            <h2 class="border-b border-line px-5 py-3 text-sm font-semibold">Ledger</h2>
            @forelse ($transactions as $tx)
                <div class="flex items-center justify-between gap-3 border-b border-line/60 px-5 py-2.5 text-sm last:border-0">
                    <div class="min-w-0"><div class="truncate">{{ $tx->description }}</div><div class="text-xs text-faint">{{ $tx->type->label() }} · {{ $tx->bucket->value }} · {{ $tx->created_at->format('M j H:i') }}</div></div>
                    <div class="text-right mono-num {{ $tx->amount->isNegative() ? 'text-ink-2' : 'text-secondary' }}">{{ $tx->amount->format(true) }}</div>
                </div>
            @empty
                <x-empty icon="coin" title="No transactions" class="!py-8" />
            @endforelse
        </div>
        <div class="card">
            @if ($user->isDeveloper())
                <h2 class="border-b border-line px-5 py-3 text-sm font-semibold">Recent submissions</h2>
                @forelse ($submissions as $s)
                    <a href="{{ route('admin.submissions.show', $s) }}" class="flex items-center justify-between gap-3 border-b border-line/60 px-5 py-2.5 text-sm last:border-0 hover:bg-surface-2/60">
                        <span class="truncate">{{ $s->task->title }}</span><x-status :value="$s->status" />
                    </a>
                @empty
                    <x-empty icon="list" title="No submissions" class="!py-8" />
                @endforelse
            @elseif ($user->isRequester())
                <h2 class="border-b border-line px-5 py-3 text-sm font-semibold">Recent tasks</h2>
                @forelse ($tasks as $t)
                    <a href="{{ route('admin.tasks.show', $t) }}" class="flex items-center justify-between gap-3 border-b border-line/60 px-5 py-2.5 text-sm last:border-0 hover:bg-surface-2/60">
                        <span class="truncate">{{ $t->title }}</span><x-status :value="$t->status" />
                    </a>
                @empty
                    <x-empty icon="layers" title="No tasks" class="!py-8" />
                @endforelse
            @endif
        </div>
    </div>

    <div class="card mt-6">
        <h2 class="border-b border-line px-5 py-3 text-sm font-semibold">Activity</h2>
        @include('admin._activity-rows', ['logs' => $activity])
    </div>

    <x-modal name="suspend" title="Suspend {{ $user->name }}">
        <form method="POST" action="{{ route('admin.users.suspend', $user) }}" class="space-y-4">
            @csrf
            <p class="text-sm text-muted">The user is signed out, API tokens are revoked, and they cannot claim, post or withdraw until reactivated. Balances are untouched.</p>
            <x-input name="reason" label="Reason" required />
            <div class="flex justify-end"><button class="btn-danger">Suspend account</button></div>
        </form>
    </x-modal>
    <x-modal name="adjust" title="Adjust wallet balance">
        <form method="POST" action="{{ route('admin.users.adjust-wallet', $user) }}" class="space-y-4">
            @csrf
            <p class="text-sm text-muted">Creates an immutable “adjustment” ledger entry on the available balance. Use a negative amount to debit.</p>
            <x-input name="amount" label="Amount (USD)" placeholder="5.00 or -2.50" required />
            <x-input name="note" label="Note (visible in the ledger)" required />
            <div class="flex justify-end"><button class="btn-primary">Post adjustment</button></div>
        </form>
    </x-modal>
</x-layouts.app>
