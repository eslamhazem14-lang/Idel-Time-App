<x-layouts.app title="Connect Claude Code">
    <x-page-header title="Connect Claude Code" subtitle="When Claude starts working, Watch & earn opens in your browser. When it finishes, the page tells you." />

    @php
        $script = $baseUrl.'/integrations/claude-code/idletime-hook.sh';
        $install = "mkdir -p ~/.idletime && curl -fsSL {$script} -o ~/.idletime/idletime-hook.sh && chmod +x ~/.idletime/idletime-hook.sh\n"
            ."cat > ~/.idletime/config <<'CFG'\nIDLETIME_URL={$baseUrl}\nIDLETIME_TOKEN=".($token ?? 'PASTE_YOUR_TOKEN_HERE')."\nIDLETIME_EXPECTED_MINUTES=5\nIDLETIME_OPEN_COOLDOWN=20\nCFG";
        $hooks = json_encode(['hooks' => [
            'UserPromptSubmit' => [['hooks' => [['type' => 'command', 'command' => '~/.idletime/idletime-hook.sh start']]]],
            'Stop' => [['hooks' => [['type' => 'command', 'command' => '~/.idletime/idletime-hook.sh stop']]]],
        ]], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    @endphp

    <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
        <div class="space-y-6">
            <section class="card card-pad">
                <h2 class="text-sm font-semibold"><span class="text-primary">1.</span> Create a token</h2>
                <p class="mt-1 text-sm text-muted">The hook uses it to tell us when Claude starts and stops. It can't withdraw money.</p>
                @if ($token)
                    <div class="mt-4 rounded-lg border border-secondary/30 bg-secondary/10 p-3">
                        <div class="text-xs text-secondary">Your token. Copy it now; it won't be shown again.</div>
                        <code class="mt-1 block break-all font-mono text-sm text-ink">{{ $token }}</code>
                    </div>
                @endif
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <form method="POST" action="{{ route('developer.connect.token') }}">@csrf
                        <button class="btn-primary">{{ $existing ? 'Create a new token' : 'Create token' }}</button>
                    </form>
                    @if ($existing)
                        <form method="POST" action="{{ route('developer.connect.revoke') }}">@csrf @method('DELETE')
                            <button class="btn-ghost">Revoke</button>
                        </form>
                        <span class="text-xs text-faint">Active token created {{ $existing->created_at->diffForHumans() }}{{ $existing->last_used_at ? ', last used '.$existing->last_used_at->diffForHumans() : '' }}.</span>
                    @endif
                </div>
            </section>

            <section class="card card-pad" x-data="{ copied: false }">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold"><span class="text-primary">2.</span> Install the hook</h2>
                    <button type="button" class="btn-ghost btn-sm" @click="navigator.clipboard.writeText($refs.install.innerText); copied = true; setTimeout(() => copied = false, 1500)" x-text="copied ? 'Copied' : 'Copy'"></button>
                </div>
                <p class="mt-1 text-sm text-muted">Run this in a terminal (macOS, Linux, WSL or Git Bash on Windows).</p>
                <pre class="mt-3 overflow-x-auto rounded-lg border border-line bg-bg p-3 font-mono text-xs text-ink-2" x-ref="install">{{ $install }}</pre>
            </section>

            <section class="card card-pad" x-data="{ copied: false }">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold"><span class="text-primary">3.</span> Add it to Claude Code</h2>
                    <button type="button" class="btn-ghost btn-sm" @click="navigator.clipboard.writeText($refs.hooks.innerText); copied = true; setTimeout(() => copied = false, 1500)" x-text="copied ? 'Copied' : 'Copy'"></button>
                </div>
                <p class="mt-1 text-sm text-muted">Merge this into <code class="font-mono text-ink-2">~/.claude/settings.json</code> to use it in every project. You can also run <code class="font-mono text-ink-2">/hooks</code> inside Claude Code.</p>
                <pre class="mt-3 overflow-x-auto rounded-lg border border-line bg-bg p-3 font-mono text-xs text-ink-2" x-ref="hooks">{{ $hooks }}</pre>
            </section>
        </div>

        <aside class="card card-pad h-fit text-sm text-muted">
            <h2 class="mb-2 text-sm font-semibold text-ink">What happens</h2>
            <ul class="space-y-2 text-xs">
                <li><span class="text-ink-2">You send Claude a prompt:</span> we record that your agent is busy and open <a href="{{ route('developer.watch') }}" class="underline hover:text-ink">Watch & earn</a> (at most once every 20 minutes, so tabs don't pile up).</li>
                <li><span class="text-ink-2">Claude finishes:</span> the page shows "Claude finished" and can send a desktop notification.</li>
                <li>The hook runs in the background and never slows Claude down. Set <code class="font-mono">IDLETIME_OPEN_BROWSER=0</code> in the config to stop it opening tabs.</li>
            </ul>
        </aside>
    </div>
</x-layouts.app>
