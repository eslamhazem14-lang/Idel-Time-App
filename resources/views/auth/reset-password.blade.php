<x-layouts.auth heading="Choose a new password">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-input name="email" type="email" label="Email" :value="$email" required />
        <x-input name="password" type="password" label="New password" autocomplete="new-password" required />
        <x-input name="password_confirmation" type="password" label="Confirm password" autocomplete="new-password" required />
        <button class="btn-primary w-full">Reset password</button>
    </form>
</x-layouts.auth>
