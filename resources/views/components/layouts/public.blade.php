@props(['title' => null, 'description' => null])
<x-layouts.base :title="$title" :description="$description">
    <header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-line/70 bg-bg/80 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
            <x-logo />
            <nav class="hidden items-center gap-1 text-sm md:flex" aria-label="Main">
                @foreach (['how-it-works' => 'How it works', 'developers' => 'For developers', 'requesters' => 'For businesses', 'pricing' => 'Pricing', 'faq' => 'FAQ'] as $route => $label)
                    <a href="{{ route($route) }}" class="rounded-md px-3 py-1.5 {{ request()->routeIs($route) ? 'text-ink' : 'text-muted hover:text-ink' }}">{{ $label }}</a>
                @endforeach
            </nav>
            <div class="hidden items-center gap-2 md:flex">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-primary">Open dashboard <x-icon name="arrow-right" class="size-4" /></a>
                @else
                    <a href="{{ route('login') }}" class="btn-ghost">Sign in</a>
                    <a href="{{ route('register') }}" class="btn-primary">Start Earning</a>
                @endauth
            </div>
            <button class="btn-ghost md:hidden" @click="open = !open" aria-label="Menu"><x-icon name="menu" class="size-5" /></button>
        </div>
        <div x-show="open" x-cloak x-transition class="border-t border-line px-4 py-3 md:hidden">
            @foreach (['how-it-works' => 'How it works', 'developers' => 'For developers', 'requesters' => 'For businesses', 'pricing' => 'Pricing', 'faq' => 'FAQ'] as $route => $label)
                <a href="{{ route($route) }}" class="block rounded-md px-2 py-2 text-sm text-ink-2">{{ $label }}</a>
            @endforeach
            <div class="mt-3 flex gap-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-primary flex-1">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn-secondary flex-1">Sign in</a>
                    <a href="{{ route('register') }}" class="btn-primary flex-1">Start Earning</a>
                @endauth
            </div>
        </div>
    </header>

    <main>{{ $slot }}</main>

    <footer class="mt-24 border-t border-line">
        <div class="mx-auto grid max-w-6xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-4">
            <div class="md:col-span-2">
                <x-logo />
                <p class="mt-3 max-w-sm text-sm text-muted">Small, paid technical tasks for the minutes your AI coding agent is busy. Earnings depend on task availability, qualification, and approval.</p>
            </div>
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-muted">Product</h3>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a class="text-ink-2 hover:text-ink" href="{{ route('how-it-works') }}">How it works</a></li>
                    <li><a class="text-ink-2 hover:text-ink" href="{{ route('developers') }}">For developers</a></li>
                    <li><a class="text-ink-2 hover:text-ink" href="{{ route('requesters') }}">For businesses</a></li>
                    <li><a class="text-ink-2 hover:text-ink" href="{{ route('pricing') }}">Pricing</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-muted">Help</h3>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a class="text-ink-2 hover:text-ink" href="{{ route('faq') }}">FAQ</a></li>
                    <li><a class="text-ink-2 hover:text-ink" href="{{ route('login') }}">Sign in</a></li>
                    <li><a class="text-ink-2 hover:text-ink" href="{{ route('register') }}">Create account</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-line py-5 text-center text-xs text-faint">© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</div>
    </footer>
</x-layouts.base>
