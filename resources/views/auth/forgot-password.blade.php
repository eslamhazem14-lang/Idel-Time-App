<x-layouts.auth heading="Reset your password" subheading="We'll email you a link to choose a new password.">
    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <x-input name="email" type="email" label="Email" required autofocus />
        <button class="btn-primary w-full">Email reset link</button>
    </form>
    <x-slot:footer><a href="{{ route('login') }}" class="text-ink hover:underline">Back to sign in</a></x-slot:footer>
</x-layouts.auth>
