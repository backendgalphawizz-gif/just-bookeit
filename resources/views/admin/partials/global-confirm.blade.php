@php
    $confirmReasonRestore = null;

    foreach (['rejection_reason', 'suspension_reason'] as $confirmField) {
        if ($errors->has($confirmField)) {
            $confirmReasonRestore = [
                'field' => $confirmField,
                'value' => old($confirmField, ''),
                'error' => $errors->first($confirmField),
            ];
            break;
        }
    }
@endphp

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

            <div class="jb-modal-alert-reason" x-show="$store.jbConfirm.requiresReason">
                <label class="jb-label" :for="$store.jbConfirm.reasonInputId" x-text="$store.jbConfirm.reasonLabel"></label>
                <textarea
                    class="jb-textarea"
                    rows="3"
                    :id="$store.jbConfirm.reasonInputId"
                    :maxlength="$store.jbConfirm.reasonMax"
                    x-model="$store.jbConfirm.reason"
                    x-init="$watch('$store.jbConfirm.open', open => { if (open && $store.jbConfirm.requiresReason) $nextTick(() => $el.focus()) })"
                    @keydown.enter.prevent="$store.jbConfirm.confirm()"
                    placeholder="Explain why this action is being taken..."
                ></textarea>
                <p class="jb-modal-alert-reason-count" x-show="$store.jbConfirm.requiresReason">
                    <span x-text="$store.jbConfirm.reason.trim().length"></span>
                    <span> / </span>
                    <span x-text="$store.jbConfirm.reasonMax"></span>
                    <span> max · 5 min</span>
                </p>
                <p class="jb-modal-alert-reason-hint jb-modal-alert-reason-hint--error" x-show="$store.jbConfirm.reasonError" x-text="$store.jbConfirm.reasonError"></p>
            </div>

            <div class="jb-modal-alert-actions">
                <button type="button" class="jb-modal-alert-btn jb-modal-alert-btn--ghost" @click="$store.jbConfirm.close()">
                    Cancel
                </button>
                <button
                    type="button"
                    class="jb-modal-alert-btn"
                    x-text="$store.jbConfirm.confirmLabel"
                    @click="$store.jbConfirm.confirm()"
                ></button>
            </div>
        </div>
    </div>
</template>

<script>
    document.addEventListener('alpine:init', () => {
        const PENDING_KEY = 'jb-confirm-pending';
        const restorePayload = @json($confirmReasonRestore);

        Alpine.store('jbConfirm', {
            open: false,
            title: 'Are you sure?',
            message: '',
            variant: 'warning',
            confirmLabel: 'Confirm',
            requiresReason: false,
            reasonLabel: 'Rejection reason',
            reasonName: 'rejection_reason',
            reasonInputId: 'jb-confirm-reason',
            reasonMax: 500,
            reason: '',
            reasonError: '',
            _form: null,

            readFormOptions(form) {
                return {
                    title: form.dataset.jbConfirmTitle || 'Are you sure?',
                    message: form.dataset.jbConfirm || '',
                    variant: form.dataset.jbConfirmVariant || 'warning',
                    confirmLabel: form.dataset.jbConfirmLabel || 'Confirm',
                    requiresReason: Boolean(form.dataset.jbConfirmRequiresReason),
                    reasonLabel: form.dataset.jbConfirmRequiresReason || 'Rejection reason',
                    reasonName: form.dataset.jbConfirmReasonName || 'rejection_reason',
                    reasonMax: Number(form.dataset.jbConfirmReasonMax || 500),
                };
            },

            applyOptions(options = {}) {
                this.title = options.title || 'Are you sure?';
                this.message = options.message || '';
                this.variant = options.variant || 'warning';
                this.confirmLabel = options.confirmLabel || 'Confirm';
                this.requiresReason = Boolean(options.requiresReason);
                this.reasonLabel = options.reasonLabel || 'Rejection reason';
                this.reasonName = options.reasonName || 'rejection_reason';
                this.reasonMax = Number(options.reasonMax || 500);
            },

            findFormByAction(action) {
                if (! action) {
                    return null;
                }

                return Array.from(document.querySelectorAll('form[data-jb-confirm]')).find((form) => form.action === action) || null;
            },

            savePending() {
                if (! this._form) {
                    return;
                }

                sessionStorage.setItem(PENDING_KEY, JSON.stringify({
                    action: this._form.action,
                    options: this.readFormOptions(this._form),
                    reason: this.reason,
                }));
            },

            clearPending() {
                sessionStorage.removeItem(PENDING_KEY);
            },

            ask(form, options = {}) {
                this.applyOptions({
                    ...this.readFormOptions(form),
                    ...options,
                });
                this.reasonInputId = 'jb-confirm-reason-' + Date.now();
                this.reason = '';
                this.reasonError = '';
                this._form = form;
                this.open = true;
            },

            reopen(form, options = {}, reason = '', reasonError = '') {
                this.applyOptions({
                    ...this.readFormOptions(form),
                    ...options,
                });
                this.reasonInputId = 'jb-confirm-reason-' + Date.now();
                this.reason = reason;
                this.reasonError = reasonError;
                this._form = form;
                this.open = true;
            },

            confirm() {
                if (this.requiresReason) {
                    const reasonField = document.getElementById(this.reasonInputId);
                    const reason = (reasonField?.value || this.reason || '').trim();
                    this.reason = reason;

                    if (reason.length < 5) {
                        this.reasonError = 'Enter at least 5 characters.';
                        reasonField?.focus();

                        return;
                    }

                    if (reason.length > this.reasonMax) {
                        this.reasonError = 'Reason must not be greater than ' + this.reasonMax + ' characters.';
                        reasonField?.focus();

                        return;
                    }

                    if (! this._form) {
                        return;
                    }

                    this.reasonError = '';
                    this._form.querySelectorAll(`input[name="${this.reasonName}"]`).forEach((input) => input.remove());

                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = this.reasonName;
                    input.value = reason;
                    this._form.appendChild(input);

                    this.savePending();
                }

                if (! this._form) {
                    return;
                }

                this._form.dataset.jbConfirmed = '1';

                if (typeof this._form.requestSubmit === 'function') {
                    this._form.requestSubmit();
                } else {
                    this._form.submit();
                }

                this.close(false);
            },

            close(clearPending = true) {
                this.open = false;
                this.requiresReason = false;
                this.reason = '';
                this.reasonError = '';
                this._form = null;

                if (clearPending) {
                    this.clearPending();
                }
            },

            restoreAfterValidation() {
                if (! restorePayload) {
                    this.clearPending();
                    return;
                }

                let pending = null;

                try {
                    pending = JSON.parse(sessionStorage.getItem(PENDING_KEY) || 'null');
                } catch (error) {
                    pending = null;
                }

                const reasonName = restorePayload.field || pending?.options?.reasonName || 'rejection_reason';
                const form = this.findFormByAction(pending?.action)
                    || Array.from(document.querySelectorAll('form[data-jb-confirm]')).find((candidate) => {
                        const fieldName = candidate.dataset.jbConfirmReasonName || 'rejection_reason';

                        return fieldName === reasonName;
                    });

                if (! form) {
                    return;
                }

                const options = pending?.options || this.readFormOptions(form);
                const reason = restorePayload.value || pending?.reason || '';

                this.reopen(form, options, reason, restorePayload.error || '');
            },
        });

        if (restorePayload) {
            Alpine.store('jbConfirm').restoreAfterValidation();
        } else {
            Alpine.store('jbConfirm').clearPending();
        }
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

        Alpine.store('jbConfirm').ask(form);
    }, true);
</script>
