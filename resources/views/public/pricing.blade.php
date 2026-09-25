<x-layouts.public title="Pricing" description="Developers keep 100% of the reward. Requesters pay the reward plus a transparent platform commission.">
    @php $pct = rtrim(rtrim($commission, '0'), '.'); @endphp
    <section class="mx-auto max-w-4xl px-4 pt-16 sm:px-6">
        <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">Simple commission pricing</h1>
        <p class="mt-3 text-muted">No subscriptions. Requesters pay the developer reward plus a <span class="text-ink">{{ $pct }}%</span> platform commission. Developers keep 100% of the listed reward.</p>

        <div class="card mt-10 overflow-x-auto">
            <table class="table">
                <thead><tr><th>Developer reward</th><th>Platform fee ({{ $pct }}%)</th><th>Requester pays</th></tr></thead>
                <tbody>
                    @foreach ($examples as $q)
                        <tr>
                            <td class="font-semibold text-secondary mono-num">{{ $q->reward->format() }}</td>
                            <td class="mono-num">{{ $q->platformFee->format() }}</td>
                            <td class="font-semibold mono-num">{{ $q->unitCost()->format() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-8 grid gap-4 sm:grid-cols-2">
            <div class="card card-pad">
                <h2 class="font-semibold">For requesters</h2>
                <ul class="mt-3 space-y-2 text-sm text-muted">
                    <li>• Budget = (reward + fee) × number of workers, reserved when you post.</li>
                    <li>• You are charged only for approved submissions.</li>
                    <li>• Rejected work re-opens the slot; unused budget is refunded when the task closes.</li>
                    <li>• Funds are added by bank transfer or PayPal and confirmed manually.</li>
                </ul>
            </div>
            <div class="card card-pad">
                <h2 class="font-semibold">For developers</h2>
                <ul class="mt-3 space-y-2 text-sm text-muted">
                    <li>• No fees on rewards and no platform withdrawal fee.</li>
                    <li>• Minimum withdrawal: {{ settings()->money('min_withdrawal')->format() }}.</li>
                    <li>• Earnings depend on task availability, qualification, and approval.</li>
                </ul>
            </div>
        </div>
        <p class="mt-6 text-xs text-faint">Fees are rounded to the nearest cent. Commission may change; changes apply to tasks created afterwards.</p>
    </section>
</x-layouts.public>
