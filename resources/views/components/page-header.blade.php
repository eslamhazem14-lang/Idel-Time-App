@props(['title', 'subtitle' => null, 'back' => null])
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}" class="mb-2 inline-flex items-center gap-1 text-xs text-muted hover:text-ink"><x-icon name="chevron-left" class="size-3.5" /> Back</a>
        @endif
        <h1 class="text-xl font-semibold tracking-tight text-ink sm:text-2xl">{{ $title }}</h1>
        @if ($subtitle)<p class="mt-1 text-sm text-muted">{{ $subtitle }}</p>@endif
    </div>
    @if (trim($slot))<div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>@endif
</div>
