@props([
    'variant' => 'view',
    'href' => null,
    'type' => 'button',
    'confirm' => null,
])

@php
    $iconOnly = in_array($variant, ['view', 'edit', 'delete'], true);
    $class = 'jb-action-btn jb-action-btn--' . $variant . ($iconOnly ? ' jb-action-btn--icon-only' : '');
    $attrs = $attributes->merge(['class' => $class]);
    $label = ! $slot->isEmpty() ? $slot : match ($variant) {
        'edit' => 'Edit',
        'delete' => 'Delete',
        'approve' => 'Approve',
        default => 'View',
    };
    $accessibleLabel = strip_tags((string) $label);
    if ($iconOnly) {
        $attrs = $attrs->merge(['aria-label' => $accessibleLabel, 'title' => $accessibleLabel]);
    }
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attrs }}>
        <span class="jb-action-btn__icon" aria-hidden="true">@include('admin.partials.action-icon', ['variant' => $variant])</span>
        @unless ($iconOnly)
            <span class="jb-action-btn__label">{{ $label }}</span>
        @endunless
    </a>
@else
    <button
        type="{{ $type }}"
        {{ $attrs }}
        @if ($confirm) onclick="return confirm(@js($confirm))" @endif
    >
        <span class="jb-action-btn__icon" aria-hidden="true">@include('admin.partials.action-icon', ['variant' => $variant])</span>
        @unless ($iconOnly)
            <span class="jb-action-btn__label">{{ $label }}</span>
        @endunless
    </button>
@endif
