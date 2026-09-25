@props(['name', 'label' => null, 'value' => null, 'hint' => null, 'rows' => 4, 'id' => null])
@php
    $id = $id ?? 'f_'.str_replace(['[', ']', '.'], '_', $name);
    $dot = str_replace(['[', ']'], ['.', ''], $name);
    $current = old($dot, $value);
    $current = is_array($current) ? implode(PHP_EOL, $current) : $current;
@endphp
<div>
    @if ($label)<label for="{{ $id }}" class="label">{{ $label }}</label>@endif
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" {{ $attributes->class(['field', 'field-error' => $errors->has($dot)]) }}>{{ $current }}</textarea>
    @if ($hint && ! $errors->has($dot))<p class="mt-1 text-xs text-faint">{{ $hint }}</p>@endif
    @error($dot)<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
</div>
