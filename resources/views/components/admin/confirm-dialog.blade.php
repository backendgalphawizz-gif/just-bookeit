@props([
    'show' => 'confirmOpen',
    'title' => 'Are you sure?',
    'message' => '',
    'variant' => 'warning',
])

<div
    class="jb-modal-alert"
    x-show="{{ $show }}"
    x-cloak
    @keydown.escape.window="{{ $show }} = false"
    role="alertdialog"
    aria-modal="true"
    aria-labelledby="jb-confirm-title-{{ $show }}"
    aria-describedby="jb-confirm-desc-{{ $show }}"
>
    <div
        class="jb-modal-alert-backdrop"
        x-show="{{ $show }}"
        x-transition:enter="jb-modal-enter"
        x-transition:enter-start="jb-modal-enter-start"
        x-transition:enter-end="jb-modal-enter-end"
        x-transition:leave="jb-modal-leave"
        x-transition:leave-start="jb-modal-leave-start"
        x-transition:leave-end="jb-modal-leave-end"
        @click="{{ $show }} = false"
    ></div>

    <div
        class="jb-modal-alert-card"
        x-show="{{ $show }}"
        x-transition:enter="jb-modal-card-enter"
        x-transition:enter-start="jb-modal-card-enter-start"
        x-transition:enter-end="jb-modal-card-enter-end"
        x-transition:leave="jb-modal-card-leave"
        x-transition:leave-start="jb-modal-card-leave-start"
        x-transition:leave-end="jb-modal-card-leave-end"
        @click.stop
    >
        <div class="jb-modal-alert-icon-wrap jb-modal-alert-icon-wrap--{{ $variant }}">
            <div class="jb-modal-alert-icon-ring"></div>
            <div class="jb-modal-alert-icon">
                @if ($variant === 'error')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                @else
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M12 3a9 9 0 100 18 9 9 0 000-18z" />
                    </svg>
                @endif
            </div>
        </div>

        <h2 id="jb-confirm-title-{{ $show }}" class="jb-modal-alert-title">{{ $title }}</h2>
        @if ($message)
            <p id="jb-confirm-desc-{{ $show }}" class="jb-modal-alert-message">{{ $message }}</p>
        @endif

        <div class="jb-modal-alert-actions">
            {{ $slot }}
        </div>
    </div>
</div>
