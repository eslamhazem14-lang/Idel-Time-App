@props(['color' => 'gray'])
@php
    $colors = [
        'gray' => 'bg-white/5 text-muted ring-white/10',
        'green' => 'bg-secondary/10 text-secondary ring-secondary/25',
        'violet' => 'bg-primary/15 text-[#B3A1FF] ring-primary/30',
        'amber' => 'bg-amber/10 text-amber ring-amber/25',
        'red' => 'bg-danger/10 text-danger ring-danger/25',
    ];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset whitespace-nowrap '.($colors[$color] ?? $colors['gray'])]) }}>{{ $slot }}</span>
