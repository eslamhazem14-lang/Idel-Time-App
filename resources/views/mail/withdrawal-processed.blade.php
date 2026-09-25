<x-mail::message>
@if ($paid)
# Withdrawal paid

We sent **{{ $withdrawal->amount->format() }}** via {{ $withdrawal->method->label() }} (request #{{ $withdrawal->id }}).
@if ($withdrawal->gateway_reference)

Reference: {{ $withdrawal->gateway_reference }}
@endif
@else
# Withdrawal rejected

Your withdrawal of **{{ $withdrawal->amount->format() }}** (request #{{ $withdrawal->id }}) was rejected and the funds were returned to your available balance.

**Reason:** {{ $withdrawal->admin_note }}
@endif

<x-mail::button :url="route('developer.wallet')">Open wallet</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
