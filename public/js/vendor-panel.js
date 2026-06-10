/**
 * Vendor panel — live input restrictions (same rules as admin panel).
 */
(function () {
    const EMAIL_PATTERN = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/;

    const VP_FILTERS = {
        'person-name': (v) => v.replace(/[^\p{L}\s.'-]/gu, ''),
        city: (v) => v.replace(/[^\p{L}\s.'-]/gu, ''),
        phone: (v) => v.replace(/\D/g, '').slice(0, 10),
        title: (v) => v.replace(/[^\p{L}\p{N}\s.,'&()\-]/gu, ''),
        text: (v) => v.replace(/[^\p{L}\p{N}\s.,'!?&()\-:@#%/\\[\]\n\r]/gu, ''),
        integer: (v) => v.replace(/\D/g, ''),
        gst: (v) => v.replace(/[^0-9A-Za-z]/g, '').toUpperCase().slice(0, 15),
        'account-number': (v) => v.replace(/\D/g, '').slice(0, 20),
        ifsc: (v) => v.replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, 11),
        email: (v) => v.replace(/[^\w.@+\-]/g, '').slice(0, 255),
        otp: (v) => v.replace(/\D/g, '').slice(0, 4),
    };

    function filterValue(type, value) {
        return VP_FILTERS[type] ? VP_FILTERS[type](value) : value;
    }

    function maxLengthFor(input) {
        const fromAttr = parseInt(input.getAttribute('maxlength'), 10);
        return fromAttr > 0 ? fromAttr : null;
    }

    function clampToMaxLength(input, value) {
        const max = maxLengthFor(input);
        return max ? value.slice(0, max) : value;
    }

    function showFieldError(input, message) {
        let el = input.parentElement?.querySelector('[data-vp-live-error]');
        if (!el) {
            el = document.createElement('p');
            el.dataset.vpLiveError = '1';
            el.className = 'vp-field-error';
            input.parentElement?.appendChild(el);
        }
        el.textContent = message;
        input.classList.add('vp-input--error');
    }

    function clearFieldError(input) {
        input.parentElement?.querySelector('[data-vp-live-error]')?.remove();
        input.classList.remove('vp-input--error');
    }

    function validateEmailField(input) {
        const value = input.value.trim();
        const required = input.hasAttribute('required');
        if (value === '') {
            if (required) {
                showFieldError(input, 'This field is required.');
                return false;
            }
            clearFieldError(input);
            return true;
        }
        if (!EMAIL_PATTERN.test(value)) {
            showFieldError(input, 'Enter a valid email ID (e.g. name@example.com).');
            return false;
        }
        clearFieldError(input);
        return true;
    }

    function bindRestriction(input) {
        const type = input.dataset.vpRestrict;
        if (!type || !VP_FILTERS[type]) return;

        const apply = () => {
            const start = input.selectionStart;
            const before = input.value;
            const after = clampToMaxLength(input, filterValue(type, before));
            if (after !== before) {
                input.value = after;
                const delta = after.length - before.length;
                const pos = Math.max(0, (start ?? after.length) + delta);
                input.setSelectionRange(pos, pos);
            }
        };

        input.addEventListener('input', apply);
        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData)?.getData('text') ?? '';
            const filtered = filterValue(type, pasted);
            const start = input.selectionStart ?? input.value.length;
            const end = input.selectionEnd ?? input.value.length;
            input.value = clampToMaxLength(
                input,
                filterValue(type, input.value.slice(0, start) + filtered + input.value.slice(end))
            );
            const pos = Math.min(start + filtered.length, input.value.length);
            input.setSelectionRange(pos, pos);
            input.dispatchEvent(new Event('input', { bubbles: true }));
        });
        if (input.value) input.value = clampToMaxLength(input, filterValue(type, input.value));

        if (type === 'email') {
            const validate = () => validateEmailField(input);
            input.addEventListener('input', validate);
            input.addEventListener('blur', validate);
        }
    }

    document.querySelectorAll('[data-vp-restrict]').forEach(bindRestriction);

    document.querySelectorAll('form[method="POST"], form[method="post"]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            let valid = true;
            form.querySelectorAll('[data-vp-restrict="email"]').forEach((input) => {
                if (!validateEmailField(input)) {
                    valid = false;
                }
            });
            form.querySelectorAll('[maxlength][data-vp-restrict]').forEach((input) => {
                const max = maxLengthFor(input);
                if (max && input.value.length > max) {
                    showFieldError(
                        input,
                        'Must be at most ' + max + ' characters (' + input.value.length + '/' + max + ').'
                    );
                    valid = false;
                }
            });
            if (!valid) {
                event.preventDefault();
            }
        });
    });

    const VP_DEFAULT_MAX_FILE_BYTES = 20 * 1024 * 1024;

    const VP_ALERT_ICONS = {
        success: '<path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />',
        error: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />',
        warning: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M12 3a9 9 0 100 18 9 9 0 000-18z" />',
    };

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatMaxSize(bytes) {
        const mb = bytes / (1024 * 1024);
        return Number.isInteger(mb) ? `${mb} MB` : `${mb.toFixed(1)} MB`;
    }

    function vpShowAlert({ type = 'warning', title = 'Notice', message = '' } = {}) {
        const alertType = VP_ALERT_ICONS[type] ? type : 'warning';
        const root = document.createElement('div');
        root.className = 'vp-modal-alert';
        root.setAttribute('role', 'alertdialog');
        root.setAttribute('aria-modal', 'true');
        root.innerHTML = `
            <div class="vp-modal-alert-backdrop"></div>
            <div class="vp-modal-alert-card vp-modal-alert-card--animate">
                <div class="vp-modal-alert-icon-wrap vp-modal-alert-icon-wrap--${alertType}">
                    <div class="vp-modal-alert-icon-ring"></div>
                    <div class="vp-modal-alert-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            ${VP_ALERT_ICONS[alertType]}
                        </svg>
                    </div>
                </div>
                <h2 class="vp-modal-alert-title">${escapeHtml(title)}</h2>
                <p class="vp-modal-alert-message">${escapeHtml(message)}</p>
                <button type="button" class="vp-modal-alert-btn">OK</button>
            </div>
        `;

        const close = () => root.remove();
        root.querySelector('.vp-modal-alert-backdrop').addEventListener('click', close);
        root.querySelector('.vp-modal-alert-btn').addEventListener('click', close);
        document.body.appendChild(root);
        root.querySelector('.vp-modal-alert-btn').focus();
    }

    window.vpShowAlert = vpShowAlert;

    function bindFileInput(input) {
        const maxBytes = parseInt(input.dataset.vpMaxFileBytes || String(VP_DEFAULT_MAX_FILE_BYTES), 10);
        const label = input.dataset.vpFileLabel || 'Image';

        input.addEventListener('change', () => {
            const file = input.files?.[0];
            if (!file) {
                return;
            }

            if (file.size > maxBytes) {
                input.value = '';
                vpShowAlert({
                    type: 'warning',
                    title: 'File too large',
                    message: `${label} must be ${formatMaxSize(maxBytes)} or smaller.`,
                });
                return;
            }

            if (input.dataset.vpUploadOnly) {
                const flag = document.getElementById('profile-upload-only');
                if (flag) {
                    flag.value = input.dataset.vpUploadOnly;
                }
                input.form?.requestSubmit();
                return;
            }

            if (input.dataset.vpAutoSubmit !== undefined) {
                input.form?.submit();
            }
        });
    }

    document.querySelectorAll('input[type="file"][data-vp-max-file-bytes]').forEach(bindFileInput);
})();
