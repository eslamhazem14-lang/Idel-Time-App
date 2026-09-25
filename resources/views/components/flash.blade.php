@php
    $messages = collect(['success' => 'check-circle', 'error' => 'alert', 'info' => 'info'])
        ->filter(fn ($icon, $key) => session()->has($key));
    $styles = [
        'success' => 'border-secondary/30 bg-secondary/10 text-secondary',
        'error' => 'border-danger/30 bg-danger/10 text-danger',
        'info' => 'border-primary/30 bg-primary/10 text-[#B3A1FF]',
    ];
@endphp
@foreach ($messages as $key => $icon)
    <div x-data="{ show: true }" x-show="show" x-transition role="{{ $key === 'error' ? 'alert' : 'status' }}"
        class="mb-5 flex items-start gap-3 rounded-lg border px-4 py-3 text-sm {{ $styles[$key] }}">
        <x-icon :name="$icon" class="mt-0.5 size-4 shrink-0" />
        <div class="flex-1 text-ink">{{ session($key) }}</div>
        <button type="button" @click="show = false" class="text-muted hover:text-ink" aria-label="Dismiss"><x-icon name="x" class="size-4" /></button>
    </div>
@endforeach
@if ($errors->any() && ! session()->has('error'))
    <div class="mb-5 flex items-start gap-3 rounded-lg border border-danger/30 bg-danger/10 px-4 py-3 text-sm" role="alert">
        <x-icon name="alert" class="mt-0.5 size-4 shrink-0 text-danger" />
        <div class="text-ink">Please fix the highlighted fields{{ $errors->count() === 1 ? ': '.$errors->first() : '.' }}</div>
    </div>
@endif
