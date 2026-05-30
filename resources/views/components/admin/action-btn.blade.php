@props([
    'variant' => 'view',
    'href' => null,
    'type' => 'button',
    'confirm' => null,
    'confirmTitle' => 'Are you sure?',
    'confirmVariant' => null,
    'confirmLabel' => null,
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
    $resolvedConfirmVariant = $confirmVariant ?? ($variant === 'delete' ? 'error' : 'warning');
    $resolvedConfirmLabel = $confirmLabel ?? ($variant === 'delete' ? 'Delete' : 'Confirm');
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
        type="{{ $confirm ? 'button' : $type }}"
        {{ $attrs }}
        @if ($confirm)
            @click="$store.jbConfirm.ask($el.closest('form'), {
                title: @js($confirmTitle),
                message: @js($confirm),
                variant: @js($resolvedConfirmVariant),
                confirmLabel: @js($resolvedConfirmLabel),
            })"
        @endif
    >
        <span class="jb-action-btn__icon" aria-hidden="true">@include('admin.partials.action-icon', ['variant' => $variant])</span>
        @unless ($iconOnly)
            <span class="jb-action-btn__label">{{ $label }}</span>
        @endunless
    </button>
@endif
