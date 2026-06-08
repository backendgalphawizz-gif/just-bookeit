<template x-teleport="body">
    <div
        class="vp-modal-alert"
        x-cloak
        x-show="$store.vpConfirm.open"
        @keydown.escape.window="$store.vpConfirm.close()"
        role="alertdialog"
        aria-modal="true"
    >
        <div
            class="vp-modal-alert-backdrop"
            x-show="$store.vpConfirm.open"
            x-transition:enter="vp-modal-enter"
            x-transition:enter-start="vp-modal-enter-start"
            x-transition:enter-end="vp-modal-enter-end"
            x-transition:leave="vp-modal-leave"
            x-transition:leave-start="vp-modal-leave-start"
            x-transition:leave-end="vp-modal-leave-end"
            @click="$store.vpConfirm.close()"
        ></div>

        <div
            class="vp-modal-alert-card"
            x-show="$store.vpConfirm.open"
            x-transition:enter="vp-modal-card-enter"
            x-transition:enter-start="vp-modal-card-enter-start"
            x-transition:enter-end="vp-modal-card-enter-end"
            x-transition:leave="vp-modal-card-leave"
            x-transition:leave-start="vp-modal-card-leave-start"
            x-transition:leave-end="vp-modal-card-leave-end"
            @click.stop
        >
            <div
                class="vp-modal-alert-icon-wrap"
                :class="$store.vpConfirm.variant === 'error' ? 'vp-modal-alert-icon-wrap--error' : 'vp-modal-alert-icon-wrap--warning'"
            >
                <div class="vp-modal-alert-icon-ring"></div>
                <div class="vp-modal-alert-icon">
                    <svg x-show="$store.vpConfirm.variant === 'error'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                    <svg x-show="$store.vpConfirm.variant !== 'error'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M12 3a9 9 0 100 18 9 9 0 000-18z" />
                    </svg>
                </div>
            </div>

            <h2 class="vp-modal-alert-title" x-text="$store.vpConfirm.title"></h2>
            <p class="vp-modal-alert-message" x-show="$store.vpConfirm.message" x-text="$store.vpConfirm.message"></p>

            <div class="vp-modal-alert-actions">
                <button type="button" class="vp-modal-alert-btn vp-modal-alert-btn--ghost" @click="$store.vpConfirm.close()">
                    Cancel
                </button>
                <button type="button" class="vp-modal-alert-btn" x-text="$store.vpConfirm.confirmLabel" @click="$store.vpConfirm.confirm()"></button>
            </div>
        </div>
    </div>
</template>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('vpConfirm', {
            open: false,
            title: 'Are you sure?',
            message: '',
            variant: 'warning',
            confirmLabel: 'Confirm',
            _form: null,

            ask(form, options = {}) {
                this.title = options.title || 'Are you sure?';
                this.message = options.message || '';
                this.variant = options.variant || 'warning';
                this.confirmLabel = options.confirmLabel || 'Confirm';
                this._form = form;
                this.open = true;
            },

            confirm() {
                if (this._form) {
                    this._form.dataset.vpConfirmed = '1';
                    if (typeof this._form.requestSubmit === 'function') {
                        this._form.requestSubmit();
                    } else {
                        this._form.submit();
                    }
                }

                this.close();
            },

            close() {
                this.open = false;
                this._form = null;
            },
        });
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || !form.dataset.vpConfirm) {
            return;
        }

        if (form.dataset.vpConfirmed === '1') {
            delete form.dataset.vpConfirmed;

            return;
        }

        event.preventDefault();
        event.stopPropagation();

        Alpine.store('vpConfirm').ask(form, {
            title: form.dataset.vpConfirmTitle || 'Are you sure?',
            message: form.dataset.vpConfirm,
            variant: form.dataset.vpConfirmVariant || 'warning',
            confirmLabel: form.dataset.vpConfirmLabel || 'Confirm',
        });
    }, true);
</script>
