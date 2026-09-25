<x-mail::message>
# Verify your email address

Hi {{ $user->name }}, please confirm this is your email address to start working on or posting tasks.

<x-mail::button :url="$url">Verify email</x-mail::button>

This link expires in {{ config('auth.verification.expire', 60) }} minutes. If you didn't create an account, you can ignore this email.

{{ config('app.name') }}
</x-mail::message>
