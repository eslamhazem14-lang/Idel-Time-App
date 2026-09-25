<x-layouts.app title="Withdrawals">
    <x-page-header title="Withdrawals" subtitle="Pay out manually, then mark as paid. Rejections refund the developer automatically." />
    @include('admin._tabs', ['route' => 'admin.withdrawals.index', 'cases' => \App\Enums\WithdrawalStatus::cases(), 'current' => $status, 'all' => true])
    <div class="card overflow-x-auto">
        @if ($withdrawals->isEmpty())
            <x-empty icon="banknote" title="No withdrawals" text="Withdrawal requests from developers will appear here." />
        @else
            <table class="table">
                <thead><tr><th>#</th><th>Developer</th><th>Amount</th><th>Method</th><th>Requested</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @foreach ($withdrawals as $w)
                        <tr>
                            <td class="font-mono text-xs">#{{ $w->id }}</td>
                            <td><a href="{{ route('admin.users.show', $w->developer) }}" class="hover:text-primary">{{ $w->developer->name }}</a> @if ($w->developer->fraud_score)<x-badge color="red">risk {{ $w->developer->fraud_score }}</x-badge>@endif</td>
                            <td class="font-semibold mono-num">{{ $w->amount->format() }}</td>
                            <td>{{ $w->method->label() }}</td>
                            <td class="text-muted">{{ $w->created_at->diffForHumans() }}</td>
                            <td><x-status :value="$w->status" /></td>
                            <td class="text-right"><a href="{{ route('admin.withdrawals.show', $w) }}" class="btn-secondary btn-sm">{{ $w->status->isOpen() ? 'Process' : 'View' }}</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4">{{ $withdrawals->links() }}</div>
        @endif
    </div>
</x-layouts.app>
