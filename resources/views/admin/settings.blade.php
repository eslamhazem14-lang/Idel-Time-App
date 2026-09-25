<x-layouts.app title="Settings">
    <x-page-header title="Platform settings" subtitle="Changes apply immediately. Commission changes affect new tasks only." />
    <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
        @csrf @method('PUT')
        <div class="grid gap-6 lg:grid-cols-2">
            <section class="card card-pad space-y-4">
                <h2 class="text-sm font-semibold">Business model</h2>
                <x-input name="commission_percent" label="Platform commission (%)" :value="$settings['commission_percent']" hint="Charged to requesters on top of each reward." />
                <div class="grid grid-cols-2 gap-4">
                    <x-input name="min_reward" label="Min reward per task ($)" :value="$settings['min_reward']" />
                    <x-input name="max_reward" label="Max reward per task ($)" :value="$settings['max_reward']" />
                </div>
            </section>
            <section class="card card-pad space-y-4">
                <h2 class="text-sm font-semibold">Task duration</h2>
                <div class="grid grid-cols-2 gap-4">
                    <x-input name="min_task_minutes" type="number" label="Min task duration (min)" :value="$settings['min_task_minutes']" />
                    <x-input name="max_task_minutes" type="number" label="Max task duration (min)" :value="$settings['max_task_minutes']" />
                </div>
            </section>
            <section class="card card-pad space-y-4">
                <h2 class="text-sm font-semibold">Claims & timer</h2>
                <div class="grid grid-cols-2 gap-4">
                    <x-input name="claim_duration_multiplier" label="Claim duration × estimate" :value="$settings['claim_duration_multiplier']" hint="2 = twice the estimated time" />
                    <x-input name="claim_min_minutes" type="number" label="Minimum claim duration (min)" :value="$settings['claim_min_minutes']" />
                    <x-input name="claim_grace_seconds" type="number" label="Grace period (seconds)" :value="$settings['claim_grace_seconds']" />
                    <x-input name="expiry_warning_minutes" type="number" label="Expiry warning (min before)" :value="$settings['expiry_warning_minutes']" />
                    <x-input name="max_active_claims" type="number" label="Max concurrent claims per developer" :value="$settings['max_active_claims']" />
                    <x-select name="expired_claim_policy" label="When a claim expires" :options="['expire' => 'Discard & release slot', 'submit_draft' => 'Auto-submit saved draft if valid']" :value="$settings['expired_claim_policy']" />
                </div>
                <x-input name="auto_approve_days" type="number" label="Auto-approve unreviewed submissions after (days, 0 = never)" :value="$settings['auto_approve_days']" />
            </section>
            <section class="card card-pad space-y-4">
                <h2 class="text-sm font-semibold">Wallet</h2>
                <div class="grid grid-cols-2 gap-4">
                    <x-input name="min_withdrawal" label="Minimum withdrawal ($)" :value="$settings['min_withdrawal']" />
                    <x-input name="min_deposit" label="Minimum deposit ($)" :value="$settings['min_deposit']" />
                </div>
                <fieldset>
                    <legend class="label">Withdrawal methods</legend>
                    <div class="flex flex-wrap gap-4">
                        @foreach ($methods as $m)
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="withdrawal_methods[]" value="{{ $m->value }}" @checked(in_array($m->value, (array) $settings['withdrawal_methods'], true)) class="rounded border-line bg-bg text-primary"> {{ $m->label() }}</label>
                        @endforeach
                    </div>
                    @error('withdrawal_methods')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                </fieldset>
            </section>
            <section class="card card-pad space-y-4 lg:col-span-2">
                <h2 class="text-sm font-semibold">Watch & earn (video ads)</h2>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="ads_enabled" value="1" @checked($settings['ads_enabled']) class="rounded border-line bg-bg text-primary"> Enable the Watch & earn page</label>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <x-input name="ad_revenue_share_percent" label="Developer share of ad revenue (%)" :value="$settings['ad_revenue_share_percent']" />
                    <x-input name="ad_estimated_view_value" label="Estimated value per view ($)" :value="$settings['ad_estimated_view_value']" hint="Display only" />
                    <x-input name="ad_min_watch_seconds" type="number" label="Min watch time (s)" :value="$settings['ad_min_watch_seconds']" />
                    <x-input name="ad_daily_cap" type="number" label="Ads per developer per day" :value="$settings['ad_daily_cap']" />
                    <x-input name="ad_cooldown_seconds" type="number" label="Cooldown between ads (s)" :value="$settings['ad_cooldown_seconds']" />
                </div>
            </section>
            <section class="card card-pad space-y-4 lg:col-span-2">
                <h2 class="text-sm font-semibold">Access</h2>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="require_email_verification" value="1" @checked($settings['require_email_verification']) class="rounded border-line bg-bg text-primary"> Require verified email to claim, post and withdraw</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="maintenance_mode" value="1" @checked($settings['maintenance_mode']) class="rounded border-line bg-bg text-primary"> <span class="text-amber">Maintenance mode</span> — only admins can use the app; public pages stay online</label>
                <x-input name="maintenance_message" label="Maintenance message" :value="$settings['maintenance_message']" />
            </section>
        </div>
        <div class="flex justify-end"><button class="btn-primary btn-lg">Save settings</button></div>
    </form>
</x-layouts.app>
