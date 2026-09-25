<a href="{{ auth()->check() ? route('dashboard') : route('home') }}" {{ $attributes->merge(['class' => 'flex items-center gap-2 font-semibold tracking-tight text-ink']) }}>
    <span class="grid size-7 place-items-center rounded-lg bg-primary text-white shadow-[0_0_20px_-4px_#7C5CFC]">
        <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h3l2-5 4 10 2-5h3"/></svg>
    </span>
    <span>{{ config('app.name') }}</span>
</a>
