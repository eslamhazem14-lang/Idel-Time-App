<x-layouts.app title="Deposits">
    <x-page-header title="Deposits" subtitle="Confirm requester payments once they arrive in the platform account." />
    @include('admin._tabs', ['route' => 'admin.deposits.index', 'cases' => \App\Enums\DepositStatus::cases(), 'current' => $status, 'all' => true])
    <div class="card overflow-x-auto">
        @if ($deposits->isEmpty())
            <x-empty icon="wallet" title="No deposits" />
        @else
            <table class="table">
                <thead><tr><th>Reference</th><th>Requester</th><th>Amount</th><th>Method / their ref.</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @foreach ($deposits as $d)
                        <tr>
                            <td class="font-mono text-xs">{{ $d->gateway_reference }}</td>
                            <td>{{ $d->requester->name }}</td>
                            <td class="font-semibold mono-num">{{ $d->amount->format() }}</td>
                            <td class="text-muted">{{ $d->method->label() }}{{ $d->reference ? ' · '.$d->reference : '' }}</td>
                            <td><x-status :value="$d->status" /></td>
                            <td class="text-right">
                                @if ($d->status->value === 'pending')
                                    <div class="flex justify-end gap-2" x-data="{ reject: false }">
                                        <form method="POST" action="{{ route('admin.deposits.update', $d) }}">@csrf<input type="hidden" name="status" value="completed"><button class="btn-success btn-sm">Confirm received</button></form>
                                        <button type="button" class="btn-ghost btn-sm" @click="reject = !reject">Reject</button>
                                        <form x-show="reject" x-cloak method="POST" action="{{ route('admin.deposits.update', $d) }}" class="flex gap-2">@csrf<input type="hidden" name="status" value="rejected"><input name="note" class="field py-1 text-xs" placeholder="Reason" required><button class="btn-danger btn-sm">Reject</button></form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4">{{ $deposits->links() }}</div>
        @endif
    </div>
</x-layouts.app>
