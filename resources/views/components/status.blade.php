@props(['value'])
{{-- Renders any enum exposing label() + color() --}}
<x-badge :color="$value->color()" {{ $attributes }}>{{ $value->label() }}</x-badge>
