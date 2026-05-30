<template x-teleport="body">
    <div
        class="jb-modal-alert"
        x-cloak
        x-show="$store.jbConfirm.open"
        @keydown.escape.window="$store.jbConfirm.close()"
        role="alertdialog"
        aria-modal="true"
    >
        <div
            class="jb-modal-alert-backdrop"
            x-show="$store.jbConfirm.open"
            x-transition:enter="jb-modal-enter"
            x-transition:enter-start="jb-modal-enter-start"
            x-transition:enter-end="jb-modal-enter-end"
            x-transition:leave="jb-modal-leave"
            x-transition:leave-start="jb-modal-leave-start"
            x-transition:leave-end="jb-modal-leave-end"
            @click="$store.jbConfirm.close()"
        ></div>

        <div
            class="jb-modal-alert-card"
            x-show="$store.jbConfirm.open"
            x-transition:enter="jb-modal-card-enter"
            x-transition:enter-start="jb-modal-card-enter-start"
            x-transition:enter-end="jb-modal-card-enter-end"
            x-transition:leave="jb-modal-card-leave"
            x-transition:leave-start="jb-modal-card-leave-start"
            x-transition:leave-end="jb-modal-card-leave-end"
            @click.stop
        >
            <div
                class="jb-modal-alert-icon-wrap"
                :class="$store.jbConfirm.variant === 'error' ? 'jb-modal-alert-icon-wrap--error' : 'jb-modal-alert-icon-wrap--warning'"
            >
                <div class="jb-modal-alert-icon-ring"></div>
                <div class="jb-modal-alert-icon">
                    <svg x-show="$store.jbConfirm.variant === 'error'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                    <svg x-show="$store.jbConfirm.variant !== 'error'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M12 3a9 9 0 100 18 9 9 0 000-18z" />
                    </svg>
                </div>
            </div>

            <h2 class="jb-modal-alert-title" x-text="$store.jbConfirm.title"></h2>
            <p class="jb-modal-alert-message" x-show="$store.jbConfirm.message" x-text="$store.jbConfirm.message"></p>

            <div class="jb-modal-alert-actions">
                <button type="button" class="jb-modal-alert-btn jb-modal-alert-btn--ghost" @click="$store.jbConfirm.close()">
                    Cancel
                </button>
                <button type="button" class="jb-modal-alert-btn" x-text="$store.jbConfirm.confirmLabel" @click="$store.jbConfirm.confirm()"></button>
            </div>
        </div>
    </div>
</template>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('jbConfirm', {
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
                    this._form.dataset.jbConfirmed = '1';
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

        if (!(form instanceof HTMLFormElement) || !form.dataset.jbConfirm) {
            return;
        }

        if (form.dataset.jbConfirmed === '1') {
            delete form.dataset.jbConfirmed;

            return;
        }

        event.preventDefault();
        event.stopPropagation();

        Alpine.store('jbConfirm').ask(form, {
            title: form.dataset.jbConfirmTitle || 'Are you sure?',
            message: form.dataset.jbConfirm,
            variant: form.dataset.jbConfirmVariant || 'warning',
            confirmLabel: form.dataset.jbConfirmLabel || 'Confirm',
        });
    }, true);
</script>
