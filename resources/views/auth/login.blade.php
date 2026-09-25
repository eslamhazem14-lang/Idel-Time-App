<x-layouts.auth heading="Welcome back" subheading="Sign in to pick up where you left off.">
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <x-input name="email" type="email" label="Email" autocomplete="email" required autofocus />
        <div>
            <div class="mb-1.5 flex items-center justify-between">
                <label for="f_password" class="label mb-0">Password</label>
                <a href="{{ route('password.request') }}" class="text-xs text-muted hover:text-ink">Forgot password?</a>
            </div>
            <x-input name="password" type="password" autocomplete="current-password" required />
        </div>
        <label class="flex items-center gap-2 text-sm text-ink-2"><input type="checkbox" name="remember" class="rounded border-line bg-bg text-primary focus:ring-primary/30"> Remember me</label>
        <button class="btn-primary w-full">Sign in</button>
    </form>
    <x-slot:footer>New here? <a href="{{ route('register') }}" class="text-ink hover:underline">Create an account</a></x-slot:footer>
</x-layouts.auth>
