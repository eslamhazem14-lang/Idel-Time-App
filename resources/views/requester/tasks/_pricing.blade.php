{{-- Budget card. Expects: $commission, $balance, $rewardValue, $slotsValue, $slotsLabel, $multiplier (JS expr for batch size) --}}
<div class="card card-pad space-y-4">
    <h2 class="text-sm font-semibold">Reward & budget</h2>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="label" for="f_reward">Reward per completion (USD)</label>
            <div class="relative">
                <span class="pointer-events-none absolute left-3 top-2 text-sm text-faint">$</span>
                <input id="f_reward" name="reward" x-model="reward" inputmode="decimal" class="field pl-7 mono-num @error('reward') field-error @enderror" required>
            </div>
            <p class="mt-1 text-xs text-faint">Between ${{ $limits['min_reward'] ?? settings('min_reward') }} and ${{ $limits['max_reward'] ?? settings('max_reward') }}.</p>
            @error('reward')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="f_slots">{{ $slotsLabel }}</label>
            <input id="f_slots" name="slots" type="number" min="1" x-model="slots" class="field mono-num @error('slots') field-error @enderror" required>
            @error('slots')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
        </div>
    </div>
    <dl class="divide-y divide-line rounded-lg border border-line text-sm">
        <div class="flex justify-between px-4 py-2.5"><dt class="text-muted">Developer reward</dt><dd class="mono-num text-secondary" x-text="fmt(rewardCents)"></dd></div>
        <div class="flex justify-between px-4 py-2.5"><dt class="text-muted">Platform fee ({{ rtrim(rtrim($commission, '0'), '.') }}%)</dt><dd class="mono-num" x-text="fmt(feeCents)"></dd></div>
        <div class="flex justify-between px-4 py-2.5"><dt class="text-muted">Requester pays per completion</dt><dd class="mono-num" x-text="fmt(unitCents)"></dd></div>
        <div class="flex justify-between bg-surface-2 px-4 py-3"><dt class="font-medium">Total budget reserved</dt><dd class="font-semibold mono-num" x-text="fmt(totalCents * {{ $multiplier ?? '1' }})"></dd></div>
    </dl>
    <p class="text-xs" :class="totalCents * {{ $multiplier ?? '1' }} > {{ $balance->cents }} ? 'text-danger' : 'text-faint'">
        Available balance: {{ $balance->format() }}.
        <template x-if="totalCents * {{ $multiplier ?? '1' }} > {{ $balance->cents }}"><span>Not enough funds — <a href="{{ route('requester.billing') }}" class="underline">add funds</a>.</span></template>
    </p>
    <p class="text-[11px] text-faint">Final amounts are calculated on the server. You are only charged for approved submissions; unused budget is refunded.</p>
</div>
