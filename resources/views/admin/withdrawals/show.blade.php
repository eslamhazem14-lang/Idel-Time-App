<x-layouts.app :title="'Withdrawal #'.$withdrawal->id">
    <x-page-header :title="'Withdrawal #'.$withdrawal->id" :subtitle="$withdrawal->developer->name.' · '.$withdrawal->created_at->format('M j, Y H:i')" :back="route('admin.withdrawals.index')">
        <x-status :value="$withdrawal->status" />
    </x-page-header>
    <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
        <div class="space-y-6">
            <div class="card card-pad">
                <div class="text-xs text-muted">Amount</div>
                <div class="mt-1 text-3xl font-semibold mono-num">{{ $withdrawal->amount->format() }}</div>
                <dl class="mt-5 grid grid-cols-2 gap-y-2 text-sm">
                    <dt class="text-muted">Method</dt><dd>{{ $withdrawal->method->label() }}</dd>
                    <dt class="text-muted">Gateway</dt><dd>{{ $withdrawal->gateway }} {{ $withdrawal->gateway_reference ? '· '.$withdrawal->gateway_reference : '' }}</dd>
                    <dt class="text-muted">Developer balance</dt><dd class="mono-num">{{ $withdrawal->developer->wallet?->balance->format() }}</dd>
                    <dt class="text-muted">Fraud score</dt><dd class="{{ $withdrawal->developer->fraud_score ? 'text-danger' : '' }}">{{ $withdrawal->developer->fraud_score }}</dd>
                    @if ($withdrawal->processed_at)<dt class="text-muted">Processed</dt><dd>{{ $withdrawal->processed_at->format('M j, Y H:i') }} by {{ $withdrawal->processor?->name }}</dd>@endif
                    @if ($withdrawal->admin_note)<dt class="text-muted">Note</dt><dd>{{ $withdrawal->admin_note }}</dd>@endif
                </dl>
            </div>
            <div class="card card-pad">
                <h2 class="flex items-center gap-2 text-sm font-semibold"><x-icon name="lock" class="text-muted" /> Payout details</h2>
                <pre class="code-block mt-3 whitespace-pre-wrap">{{ $withdrawal->account_details }}</pre>
                <p class="mt-2 text-[11px] text-faint">Decrypted for this view only. Access is recorded in the activity log.</p>
            </div>
            @if ($history->isNotEmpty())
                <div class="card">
                    <h2 class="border-b border-line px-5 py-3 text-sm font-semibold">Previous withdrawals</h2>
                    @foreach ($history as $h)
                        <div class="flex justify-between border-b border-line/60 px-5 py-2.5 text-sm last:border-0"><span class="mono-num">{{ $h->amount->format() }} <span class="text-faint">{{ $h->created_at->format('M j') }}</span></span><x-status :value="$h->status" /></div>
                    @endforeach
                </div>
            @endif
        </div>
        <aside>
            @if ($withdrawal->status->isOpen())
                <div class="card card-pad space-y-4">
                    <h2 class="text-sm font-semibold">Update status</h2>
                    @if ($withdrawal->status->value === 'pending')
                        <form method="POST" action="{{ route('admin.withdrawals.update', $withdrawal) }}">@csrf<input type="hidden" name="status" value="processing"><button class="btn-secondary w-full">Mark processing</button></form>
                    @endif
                    <form method="POST" action="{{ route('admin.withdrawals.update', $withdrawal) }}" class="space-y-2 border-t border-line pt-4">
                        @csrf <input type="hidden" name="status" value="paid">
                        <x-input name="reference" label="Payment reference (optional)" />
                        <button class="btn-success w-full">Mark paid</button>
                    </form>
                    <form method="POST" action="{{ route('admin.withdrawals.update', $withdrawal) }}" class="space-y-2 border-t border-line pt-4">
                        @csrf <input type="hidden" name="status" value="rejected">
                        <x-textarea name="note" label="Rejection reason (sent to developer)" rows="2" />
                        <button class="btn-danger w-full">Reject & refund</button>
                    </form>
                </div>
            @endif
        </aside>
    </div>
</x-layouts.app>
