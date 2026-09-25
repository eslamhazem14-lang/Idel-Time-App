@props(['name', 'title', 'maxWidth' => 'max-w-lg'])
{{-- Open with: $dispatch('open-modal', 'name') --}}
<div x-data="{ open: false }" x-on:open-modal.window="if ($event.detail === '{{ $name }}') open = true" x-on:keydown.escape.window="open = false"
    x-show="open" x-cloak class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center" role="dialog" aria-modal="true">
    <div x-show="open" x-transition.opacity class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="open = false"></div>
    <div x-show="open" x-transition class="relative w-full {{ $maxWidth }} rounded-xl border border-line-strong bg-surface shadow-2xl">
        <div class="flex items-center justify-between border-b border-line px-5 py-3.5">
            <h2 class="text-sm font-semibold">{{ $title }}</h2>
            <button type="button" @click="open = false" class="text-muted hover:text-ink" aria-label="Close"><x-icon name="x" class="size-4" /></button>
        </div>
        <div class="p-5">{{ $slot }}</div>
    </div>
</div>
