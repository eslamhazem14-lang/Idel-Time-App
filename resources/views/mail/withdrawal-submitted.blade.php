<x-mail::message>
# Withdrawal request received

We received your request to withdraw **{{ $withdrawal->amount->format() }}** via {{ $withdrawal->method->label() }} (request #{{ $withdrawal->id }}).

Withdrawals are reviewed and paid manually, usually within a few business days. We'll email you when it's processed.

If you didn't make this request, secure your account immediately by changing your password.

{{ config('app.name') }}
</x-mail::message>
