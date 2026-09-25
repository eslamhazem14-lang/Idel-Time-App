<x-layouts.auth heading="Create your account" subheading="Choose how you'll use the platform.">
    <form method="POST" action="{{ route('register') }}" class="space-y-4" x-data="{ role: '{{ old('role', $role) }}' }">
        @csrf
        <fieldset>
            <legend class="label">I want to</legend>
            <div class="grid grid-cols-2 gap-2">
                @foreach (['developer' => ['terminal', 'Earn as a developer', 'Complete microtasks'], 'requester' => ['layers', 'Post tasks', 'Get work validated']] as $value => [$icon, $title, $text])
                    <label class="cursor-pointer rounded-lg border p-3 transition" :class="role === '{{ $value }}' ? 'border-primary bg-primary/10' : 'border-line hover:border-line-strong'">
                        <input type="radio" name="role" value="{{ $value }}" x-model="role" class="sr-only">
                        <x-icon :name="$icon" class="size-4 text-primary" />
                        <div class="mt-2 text-sm font-medium">{{ $title }}</div>
                        <div class="text-xs text-muted">{{ $text }}</div>
                    </label>
                @endforeach
            </div>
            @error('role')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
        </fieldset>
        <x-input name="name" label="Name" autocomplete="name" required />
        <x-input name="email" type="email" label="Email" autocomplete="email" required />
        <x-input name="password" type="password" label="Password" autocomplete="new-password" required hint="At least 8 characters." />
        <x-input name="password_confirmation" type="password" label="Confirm password" autocomplete="new-password" required />
        <input type="hidden" name="timezone" x-init="$el.value = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC'">
        <label class="flex items-start gap-2 text-sm text-ink-2">
            <input type="checkbox" name="terms" value="1" class="mt-0.5 rounded border-line bg-bg text-primary" @checked(old('terms'))>
            <span>I agree to the terms of service and understand earnings depend on task availability, qualification, and approval.</span>
        </label>
        @error('terms')<p class="-mt-2 text-xs text-danger">{{ $message }}</p>@enderror
        <button class="btn-primary w-full">Create account</button>
    </form>
    <x-slot:footer>Already have an account? <a href="{{ route('login') }}" class="text-ink hover:underline">Sign in</a></x-slot:footer>
</x-layouts.auth>
