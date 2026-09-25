@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'hint' => null, 'id' => null])
@php
    $id = $id ?? 'f_'.str_replace(['[', ']', '.'], '_', $name);
    $dot = str_replace(['[', ']'], ['.', ''], $name);
@endphp
<div>
    @if ($label)<label for="{{ $id }}" class="label">{{ $label }}</label>@endif
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
        @if ($type !== 'password' && $type !== 'file') value="{{ is_array($v = old($dot, $value)) ? implode(', ', $v) : $v }}" @endif
        {{ $attributes->class(['field', 'field-error' => $errors->has($dot)]) }} />
    @if ($hint && ! $errors->has($dot))<p class="mt-1 text-xs text-faint">{{ $hint }}</p>@endif
    @error($dot)<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
</div>
