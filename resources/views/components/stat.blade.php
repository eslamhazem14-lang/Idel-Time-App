@props(['label', 'value', 'hint' => null, 'icon' => null, 'accent' => false])
<div {{ $attributes->merge(['class' => 'card card-pad']) }}>
    <div class="flex items-center justify-between text-xs font-medium text-muted">
        <span>{{ $label }}</span>
        @if ($icon)<x-icon :name="$icon" class="size-4 text-faint" />@endif
    </div>
    <div class="mt-2 text-2xl font-semibold tracking-tight mono-num {{ $accent ? 'text-secondary' : 'text-ink' }}">{{ $value }}</div>
    @if ($hint)<div class="mt-1 text-xs text-faint">{{ $hint }}</div>@endif
</div>
