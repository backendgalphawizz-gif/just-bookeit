@props([
    'variant' => 'secondary',
    'href' => null,
    'type' => 'button',
    'size' => '',
])

@php
    $class = 'jb-btn ' . match ($variant) {
        'primary' => 'jb-btn-primary',
        'danger' => 'jb-btn-danger',
        'ghost' => 'jb-btn-ghost',
        'success' => 'jb-btn-success',
        default => 'jb-btn-secondary',
    } . ($size === 'sm' ? ' jb-btn-sm' : '');
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</button>
@endif
