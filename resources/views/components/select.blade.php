@props(['name', 'label' => null, 'options' => [], 'value' => null, 'placeholder' => null, 'hint' => null, 'id' => null])
@php
    $id = $id ?? 'f_'.str_replace(['[', ']', '.'], '_', $name);
    $dot = str_replace(['[', ']'], ['.', ''], $name);
    $current = (string) old($dot, $value);
@endphp
<div>
    @if ($label)<label for="{{ $id }}" class="label">{{ $label }}</label>@endif
    <select id="{{ $id }}" name="{{ $name }}" {{ $attributes->class(['field pr-8', 'field-error' => $errors->has($dot)]) }}>
        @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
        @foreach ($options as $key => $text)
            <option value="{{ $key }}" @selected($current === (string) $key)>{{ $text }}</option>
        @endforeach
    </select>
    @if ($hint && ! $errors->has($dot))<p class="mt-1 text-xs text-faint">{{ $hint }}</p>@endif
    @error($dot)<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
</div>
