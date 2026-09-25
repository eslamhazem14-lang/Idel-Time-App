<x-layouts.app title="Account">
    <x-page-header title="Account" subtitle="Your profile and security settings." />
    <div class="grid gap-6 lg:grid-cols-[1fr_380px]">
        <form method="POST" action="{{ route('account.update') }}" class="card card-pad space-y-4">
            @csrf @method('PUT')
            <h2 class="text-sm font-semibold">Profile</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-input name="name" label="Name" :value="$user->name" required />
                <x-input name="email_display" label="Email" :value="$user->email" disabled hint="Contact support to change your email." />
                <x-input name="country" label="Country code" :value="$user->country" maxlength="2" placeholder="US" />
                <x-select name="timezone" label="Timezone" :options="collect(timezone_identifiers_list())->mapWithKeys(fn ($t) => [$t => $t])->all()" :value="$user->timezone" />
            </div>
            <x-textarea name="bio" label="Bio" rows="3" :value="$user->bio" />
            <x-input name="skills" label="Skills" :value="implode(', ', $user->skills ?? [])" placeholder="php, react, sql, testing" hint="Comma separated — used to match tasks." />
            @if ($user->isDeveloper())
                @php $dp = $user->developerProfile; @endphp
                <div class="grid gap-4 border-t border-line pt-4 sm:grid-cols-2">
                    <x-input name="github_url" label="GitHub URL" :value="$dp?->github_url" placeholder="https://github.com/…" />
                    <x-input name="linkedin_url" label="LinkedIn URL" :value="$dp?->linkedin_url" />
                    <x-input name="portfolio_url" label="Portfolio URL" :value="$dp?->portfolio_url" />
                    <x-input name="experience_years" type="number" label="Years of experience" :value="$dp?->experience_years" min="0" max="60" />
                    <x-input name="languages" label="Spoken languages" :value="implode(', ', $dp?->languages ?? [])" placeholder="English, Spanish" />
                </div>
            @endif
            <div class="flex justify-end"><button class="btn-primary">Save profile</button></div>
        </form>
        <form method="POST" action="{{ route('account.password') }}" class="card card-pad space-y-4 self-start">
            @csrf @method('PUT')
            <h2 class="text-sm font-semibold">Change password</h2>
            <x-input name="current_password" type="password" label="Current password" autocomplete="current-password" />
            <x-input name="password" type="password" label="New password" autocomplete="new-password" />
            <x-input name="password_confirmation" type="password" label="Confirm new password" autocomplete="new-password" />
            <button class="btn-secondary w-full">Update password</button>
        </form>
    </div>
</x-layouts.app>
