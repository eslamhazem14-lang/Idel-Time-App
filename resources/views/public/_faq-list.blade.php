<div class="divide-y divide-line rounded-xl border border-line bg-surface">
    @foreach ($faqs as [$q, $a])
        <div x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }">
            <button type="button" @click="open = !open" class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left text-sm font-medium text-ink" :aria-expanded="open">
                {{ $q }}
                <x-icon name="chevron-down" class="size-4 shrink-0 text-muted transition" x-bind:class="open && 'rotate-180'" />
            </button>
            <div x-show="open" x-transition.opacity x-cloak class="px-5 pb-4 text-sm leading-relaxed text-muted">{{ $a }}</div>
        </div>
    @endforeach
</div>
