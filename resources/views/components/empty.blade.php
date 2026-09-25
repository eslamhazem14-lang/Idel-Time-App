@props(['icon' => 'inbox', 'title', 'text' => null])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-14 text-center']) }}>
    <div class="grid size-11 place-items-center rounded-xl border border-line bg-surface-2 text-muted">
        <x-icon :name="$icon" class="size-5" />
    </div>
    <h3 class="mt-4 text-sm font-semibold text-ink">{{ $title }}</h3>
    @if ($text)<p class="mt-1 max-w-sm text-sm text-muted">{{ $text }}</p>@endif
    @if (trim($slot))<div class="mt-5">{{ $slot }}</div>@endif
</div>
