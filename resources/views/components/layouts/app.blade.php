@props(['title' => null])
@php
    $user = auth()->user();
    $nav = match ($user->role) {
        \App\Enums\UserRole::Developer => [
            ['developer.dashboard', 'Dashboard', 'grid', 'developer.dashboard'],
            ['developer.tasks.index', 'Find tasks', 'search', 'developer.tasks.*'],
            ['developer.submissions.index', 'Submissions', 'list', 'developer.submissions.*'],
            ['developer.watch', 'Watch & earn', 'play', 'developer.watch'],
            ['developer.wallet', 'Wallet', 'wallet', 'developer.wallet'],
            ['developer.connect', 'Connect Claude Code', 'terminal', 'developer.connect'],
        ],
        \App\Enums\UserRole::Requester => [
            ['requester.dashboard', 'Dashboard', 'grid', 'requester.dashboard'],
            ['requester.tasks.index', 'My tasks', 'layers', 'requester.tasks.*|requester.batches.*'],
            ['requester.submissions.index', 'Review queue', 'inbox', 'requester.submissions.*'],
            ['requester.billing', 'Billing', 'wallet', 'requester.billing'],
        ],
        \App\Enums\UserRole::Admin => [
            ['admin.dashboard', 'Overview', 'chart', 'admin.dashboard'],
            ['admin.tasks.index', 'Tasks', 'layers', 'admin.tasks.*'],
            ['admin.submissions.index', 'Submissions', 'inbox', 'admin.submissions.*'],
            ['admin.users.index', 'Users', 'users', 'admin.users.*'],
            ['admin.withdrawals.index', 'Withdrawals', 'banknote', 'admin.withdrawals.*'],
            ['admin.deposits.index', 'Deposits', 'wallet', 'admin.deposits.*'],
            ['admin.ads.index', 'Ad revenue', 'play', 'admin.ads.*'],
            ['admin.disputes.index', 'Disputes', 'scale', 'admin.disputes.*'],
            ['admin.reports.index', 'Reports', 'flag', 'admin.reports.*'],
            ['admin.fraud.index', 'Fraud signals', 'shield', 'admin.fraud.*'],
            ['admin.categories.index', 'Categories', 'tag', 'admin.categories.*'],
            ['admin.templates.index', 'Templates', 'file', 'admin.templates.*'],
            ['admin.settings.edit', 'Settings', 'settings', 'admin.settings.*'],
            ['admin.activity.index', 'Activity log', 'activity', 'admin.activity.*'],
        ],
    };
    $unread = $user->unreadNotifications()->count();
    $wallet = $user->isAdmin() ? null : app(\App\Services\WalletService::class)->walletFor($user);
@endphp
<x-layouts.base :title="$title" :noindex="true">
    <div x-data="{ nav: false }" class="min-h-screen lg:grid lg:grid-cols-[240px_1fr]">
        {{-- Sidebar --}}
        <aside :class="nav ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-line bg-surface transition lg:sticky lg:top-0 lg:h-screen lg:w-auto lg:translate-x-0">
            <div class="flex h-16 items-center justify-between px-5">
                <x-logo />
                <button class="btn-ghost -mr-2 lg:hidden" @click="nav = false" aria-label="Close menu"><x-icon name="x" class="size-5" /></button>
            </div>
            <div class="px-5 pb-3">
                <span class="text-[10px] font-semibold uppercase tracking-[0.14em] text-faint">{{ $user->role->label() }}</span>
            </div>
            <nav class="flex-1 space-y-0.5 overflow-y-auto px-3" aria-label="Sidebar">
                @foreach ($nav as [$route, $label, $icon, $pattern])
                    @php $active = request()->routeIs(...explode('|', $pattern)); @endphp
                    <a href="{{ route($route) }}" @class([
                        'flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition',
                        'bg-primary/12 text-ink ring-1 ring-inset ring-primary/25' => $active,
                        'text-muted hover:bg-surface-2 hover:text-ink' => ! $active,
                    ])>
                        <x-icon :name="$icon" class="size-4 {{ $active ? 'text-primary' : '' }}" />
                        {{ $label }}
                    </a>
                @endforeach
                @if ($user->isRequester())
                    <div class="pt-4">
                        <a href="{{ route('requester.tasks.create') }}" class="btn-primary w-full"><x-icon name="plus" class="size-4" /> Post a Task</a>
                    </div>
                @endif
            </nav>
            @if ($wallet)
                <div class="m-3 rounded-lg border border-line bg-bg/60 p-3">
                    <div class="text-[11px] text-muted">{{ $user->isDeveloper() ? 'Available balance' : 'Available funds' }}</div>
                    <div class="mt-0.5 text-lg font-semibold mono-num {{ $user->isDeveloper() ? 'text-secondary' : 'text-ink' }}">{{ $wallet->balance->format() }}</div>
                    <div class="text-[11px] text-faint mono-num">{{ $wallet->pending_balance->format() }} {{ $user->isDeveloper() ? 'pending review' : 'in escrow' }}</div>
                </div>
            @endif
        </aside>
        <div x-show="nav" x-cloak @click="nav = false" class="fixed inset-0 z-40 bg-black/60 lg:hidden"></div>

        {{-- Main --}}
        <div class="min-w-0">
            <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-line bg-bg/85 px-4 backdrop-blur sm:px-6">
                <button class="btn-ghost -ml-2 lg:hidden" @click="nav = true" aria-label="Open menu"><x-icon name="menu" class="size-5" /></button>
                <div class="flex-1"></div>
                <a href="{{ route('notifications.index') }}" class="btn-ghost relative" aria-label="Notifications">
                    <x-icon name="bell" class="size-4" />
                    @if ($unread)
                        <span class="absolute -right-0.5 -top-0.5 grid min-w-4 place-items-center rounded-full bg-primary px-1 text-[10px] font-semibold leading-4 text-white">{{ $unread > 9 ? '9+' : $unread }}</span>
                    @endif
                </a>
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" @click.outside="open = false" class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-surface-2">
                        <span class="grid size-7 place-items-center rounded-full bg-surface-3 text-[11px] font-semibold text-ink-2 ring-1 ring-line-strong">{{ $user->initials() }}</span>
                        <span class="hidden text-sm text-ink-2 sm:block">{{ $user->name }}</span>
                        <x-icon name="chevron-down" class="size-3.5 text-muted" />
                    </button>
                    <div x-show="open" x-cloak x-transition class="absolute right-0 mt-2 w-52 overflow-hidden rounded-lg border border-line-strong bg-surface-2 py-1 text-sm shadow-xl">
                        <div class="border-b border-line px-3 py-2 text-xs text-muted truncate">{{ $user->email }}</div>
                        <a href="{{ route('account.edit') }}" class="flex items-center gap-2 px-3 py-2 text-ink-2 hover:bg-surface-3"><x-icon name="user" /> Account</a>
                        <a href="{{ route('home') }}" class="flex items-center gap-2 px-3 py-2 text-ink-2 hover:bg-surface-3"><x-icon name="globe" /> Public site</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="flex w-full items-center gap-2 px-3 py-2 text-left text-ink-2 hover:bg-surface-3"><x-icon name="logout" /> Sign out</button>
                        </form>
                    </div>
                </div>
            </header>

            @if (settings('maintenance_mode') && $user->isAdmin())
                <div class="border-b border-amber/30 bg-amber/10 px-6 py-2 text-xs text-amber">Maintenance mode is ON — only administrators can use the app.</div>
            @endif
            @if (! $user->hasVerifiedEmail() && settings('require_email_verification') && ! $user->isAdmin())
                <div class="flex flex-wrap items-center gap-3 border-b border-amber/30 bg-amber/10 px-6 py-2 text-xs text-amber">
                    Verify your email address to start tasks and withdraw.
                    <form method="POST" action="{{ route('verification.send') }}">@csrf<button class="underline hover:text-ink">Resend link</button></form>
                </div>
            @endif

            <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:py-8">
                <x-flash />
                {{ $slot }}
            </main>
        </div>
    </div>
</x-layouts.base>
