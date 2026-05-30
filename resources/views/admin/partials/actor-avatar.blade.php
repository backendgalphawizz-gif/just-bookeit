@props([
    'imageUrl' => null,
    'fallbackUrl' => null,
    'label' => '?',
    'size' => 'sm',
])

@php
    $src = $imageUrl ?: $fallbackUrl;
    $sizeClass = match ($size) {
        'lg' => 'jb-actor-avatar jb-actor-avatar--lg',
        'md' => 'jb-actor-avatar jb-actor-avatar--md',
        default => 'jb-actor-avatar',
    };
    $words = preg_split('/\s+/u', trim($label), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $initials = '';
    foreach (array_slice($words, 0, 2) as $word) {
        $initials .= mb_strtoupper(mb_substr($word, 0, 1));
    }
    $initials = $initials !== '' ? $initials : '?';
@endphp

@if ($src)
    <img src="{{ $src }}" alt="{{ $label }}" class="{{ $sizeClass }}">
@else
    <span class="{{ $sizeClass }} jb-actor-avatar--initials" aria-hidden="true">{{ $initials }}</span>
@endif
