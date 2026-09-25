@props(['title' => null, 'heading', 'subheading' => null])
<x-layouts.base :title="$title ?? $heading" :noindex="true">
    <div class="grid min-h-screen place-items-center px-4 py-12 grid-bg">
        <div class="w-full max-w-md">
            <div class="mb-8 flex justify-center"><x-logo /></div>
            <div class="card p-6 shadow-2xl shadow-black/40 sm:p-8">
                <h1 class="text-lg font-semibold tracking-tight">{{ $heading }}</h1>
                @if ($subheading)<p class="mt-1 text-sm text-muted">{{ $subheading }}</p>@endif
                <div class="mt-6">
                    <x-flash />
                    {{ $slot }}
                </div>
            </div>
            @isset($footer)<div class="mt-6 text-center text-sm text-muted">{{ $footer }}</div>@endisset
        </div>
    </div>
</x-layouts.base>
