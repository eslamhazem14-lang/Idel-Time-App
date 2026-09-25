<x-layouts.auth heading="Verify your email" subheading="One more step before you can start.">
    <p class="text-sm text-ink-2">We sent a verification link to <span class="text-ink">{{ auth()->user()->email }}</span>. Click it to activate task claiming, posting and withdrawals.</p>
    <div class="mt-6 flex items-center justify-between gap-3">
        <form method="POST" action="{{ route('verification.send') }}">@csrf<button class="btn-primary">Resend email</button></form>
        <a href="{{ route('dashboard') }}" class="btn-ghost">Continue to dashboard</a>
    </div>
    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-right">@csrf<button class="text-xs text-muted hover:text-ink">Sign out</button></form>
</x-layouts.auth>
