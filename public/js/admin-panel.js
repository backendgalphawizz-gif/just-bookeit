/**
 * Admin panel scripts — no build step required. Loaded directly from public/js.
 */
(function () {
    const GST_PATTERN = /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/;
    const IFSC_PATTERN = /^[A-Z]{4}0[A-Z0-9]{6}$/;
    const PERSON_NAME_PATTERN = /^[\p{L}\s.'-]*$/u;
    const TITLE_PATTERN = /^[\p{L}\p{N}\s.,'&()\-]*$/u;
    const MAX_IMAGE_BYTES = 4 * 1024 * 1024;

    const JB_FILTERS = {
        'person-name': (value) => value.replace(/[^\p{L}\s.'-]/gu, ''),
        city: (value) => value.replace(/[^\p{L}\s.'-]/gu, ''),
        phone: (value) => value.replace(/\D/g, '').slice(0, 10),
        title: (value) => value.replace(/[^\p{L}\p{N}\s.,'&()\-]/gu, ''),
        text: (value) => value.replace(/[^\p{L}\p{N}\s.,'!?&()\-:@#%/\\[\]\n\r]/gu, ''),
        integer: (value) => value.replace(/\D/g, ''),
        decimal: (value) => {
            let cleaned = value.replace(/[^\d.]/g, '');
            const parts = cleaned.split('.');
            if (parts.length > 2) {
                cleaned = parts[0] + '.' + parts.slice(1).join('');
            }
            return cleaned;
        },
        currency: (value) => value.replace(/[^A-Za-z]/g, '').toUpperCase().slice(0, 10),
        'comma-list': (value) => value.replace(/[^\p{L}\p{N}\s,.\-]/gu, ''),
        gst: (value) => value.replace(/[^0-9A-Za-z]/g, '').toUpperCase().slice(0, 15),
        'vehicle-no': (value) => value.replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, 20),
        'account-number': (value) => value.replace(/\D/g, '').slice(0, 20),
        ifsc: (value) => value.replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, 11),
        email: (value) => value.replace(/[^\w.@+\-]/g, '').slice(0, 255),
        url: (value) => value.replace(/[^\w.:\/?#@!$&'()*+,;=%\-\[\]]/g, '').slice(0, 500),
    };

    function filterValue(type, value) {
        return JB_FILTERS[type] ? JB_FILTERS[type](value) : value;
    }

    function bindRestriction(input) {
        const type = input.dataset.jbRestrict;
        if (!type || !JB_FILTERS[type]) {
            return;
        }

        const apply = () => {
            const start = input.selectionStart;
            const before = input.value;
            const after = filterValue(type, before);
            if (after !== before) {
                input.value = after;
                const delta = after.length - before.length;
                const pos = Math.max(0, (start ?? after.length) + delta);
                input.setSelectionRange(pos, pos);
            }
        };

        input.addEventListener('input', apply);
        input.addEventListener('paste', (event) => {
            event.preventDefault();
            const pasted = (event.clipboardData || window.clipboardData)?.getData('text') ?? '';
            const filtered = filterValue(type, pasted);
            const start = input.selectionStart ?? input.value.length;
            const end = input.selectionEnd ?? input.value.length;
            input.value = filterValue(type, input.value.slice(0, start) + filtered + input.value.slice(end));
            const pos = start + filtered.length;
            input.setSelectionRange(pos, pos);
            input.dispatchEvent(new Event('input', { bubbles: true }));
        });
        input.addEventListener('drop', (event) => event.preventDefault());
        if (input.value) {
            input.value = filterValue(type, input.value);
        }
    }

    function fieldWrapper(input) {
        return (
            input.closest('.jb-profile-photo-meta, .jb-logo-upload, .jb-profile-photo-panel') ||
            input.parentElement
        );
    }

    function showFieldError(input, message) {
        const wrap = fieldWrapper(input);
        const isFile = input.type === 'file';
        const selector = isFile ? '[data-jb-file-error]' : '[data-jb-live-error]';
        let el = wrap?.querySelector(selector);
        if (!el && wrap) {
            el = document.createElement('p');
            if (isFile) {
                el.dataset.jbFileError = '1';
                el.className = 'jb-file-error-alert';
                el.setAttribute('role', 'alert');
            } else {
                el.dataset.jbLiveError = '1';
                el.className = 'mt-1.5 text-xs font-medium text-rose-600';
            }
            wrap.appendChild(el);
        }
        if (el) {
            el.textContent = message;
        }
        if (!isFile) {
            input.classList.add('border-rose-400', 'ring-rose-200');
        }
    }

    function clearFieldError(input) {
        const wrap = fieldWrapper(input);
        wrap?.querySelector('[data-jb-live-error]')?.remove();
        wrap?.querySelector('[data-jb-file-error]')?.remove();
        input.classList.remove('border-rose-400', 'ring-rose-200');
    }

    function bindCharCounter(input) {
        const max = parseInt(input.dataset.jbMaxChars, 10);
        if (!max) {
            return;
        }
        const counter = document.querySelector('[data-jb-char-count-for="' + input.id + '"]');
        if (!counter) {
            return;
        }
        const update = () => {
            counter.textContent = input.value.length + '/' + max;
            counter.classList.toggle('text-rose-600', input.value.length > max);
        };
        input.addEventListener('input', update);
        update();
    }

    function bindGstValidation(input) {
        const validate = () => {
            const value = input.value.trim().toUpperCase();
            if (value === '') {
                clearFieldError(input);
                return true;
            }
            if (value.length < 15) {
                showFieldError(input, 'GST must be 15 characters (' + value.length + '/15).');
                return false;
            }
            if (!GST_PATTERN.test(value)) {
                showFieldError(input, 'Enter a valid 15-character GSTIN.');
                return false;
            }
            clearFieldError(input);
            return true;
        };
        input.addEventListener('input', () => {
            input.value = input.value.toUpperCase().replace(/[^0-9A-Z]/g, '').slice(0, 15);
            validate();
        });
        input.addEventListener('blur', validate);
    }

    function validatePhoneValue(input) {
        const value = input.value.replace(/\D/g, '');
        const required = input.hasAttribute('required');
        if (value === '') {
            if (required) {
                showFieldError(input, 'Mobile no must be exactly 10 digits.');
                return false;
            }
            clearFieldError(input);
            return true;
        }
        if (value.length !== 10) {
            showFieldError(input, 'Enter exactly 10 digits (' + value.length + '/10).');
            return false;
        }
        clearFieldError(input);
        return true;
    }

    function bindPhoneValidation(input) {
        const validate = () => validatePhoneValue(input);
        input.addEventListener('input', validate);
        input.addEventListener('blur', validate);
    }

    function bindFileSizeLimit(input) {
        if (input.dataset.jbFileAlpine) {
            return;
        }
        const maxMb = parseFloat(input.dataset.jbMaxMb || '4');
        const maxBytes = maxMb * 1024 * 1024;
        input.addEventListener('change', () => {
            const file = input.files && input.files[0];
            if (!file) {
                clearFieldError(input);
                return;
            }
            if (file.size > maxBytes) {
                showFieldError(
                    input,
                    'Image is too large. Maximum size is ' + maxMb + ' MB (selected ' + (file.size / (1024 * 1024)).toFixed(1) + ' MB).'
                );
                input.value = '';
                input.dispatchEvent(new Event('change', { bubbles: true }));
                return;
            }
            clearFieldError(input);
        });
    }

    function validateBankAccountType(vehicle, accountType) {
        if (!accountType || accountType.disabled) {
            clearFieldError(accountType);
            return true;
        }
        if (vehicle && vehicle.value.trim() && !accountType.value) {
            showFieldError(accountType, 'Select savings or current account type.');
            return false;
        }
        clearFieldError(accountType);
        return true;
    }

    function validatePersonNameField(input) {
        const value = input.value;
        if (value === '') {
            clearFieldError(input);
            return true;
        }
        if (!PERSON_NAME_PATTERN.test(value)) {
            showFieldError(input, 'May only contain letters, spaces, dots, and hyphens.');
            return false;
        }
        clearFieldError(input);
        return true;
    }

    function validateTitleField(input) {
        const value = input.value;
        if (value === '') {
            clearFieldError(input);
            return true;
        }
        if (!TITLE_PATTERN.test(value)) {
            showFieldError(input, 'Contains invalid characters.');
            return false;
        }
        clearFieldError(input);
        return true;
    }

    function validateAccountNumberField(input) {
        const value = input.value.trim();
        if (value === '') {
            clearFieldError(input);
            return true;
        }
        if (!/^\d{1,20}$/.test(value)) {
            showFieldError(input, 'Account number must be 1–20 digits only.');
            return false;
        }
        clearFieldError(input);
        return true;
    }

    function validateIfscField(input) {
        const value = input.value.trim().toUpperCase();
        if (value === '') {
            clearFieldError(input);
            return true;
        }
        if (value.length < 11) {
            showFieldError(input, 'IFSC must be 11 characters (' + value.length + '/11).');
            return false;
        }
        if (!IFSC_PATTERN.test(value)) {
            showFieldError(input, 'Enter a valid 11-character IFSC code.');
            return false;
        }
        clearFieldError(input);
        return true;
    }

    function bindLiveFieldValidation(input, validateFn) {
        const run = () => validateFn(input);
        input.addEventListener('input', run);
        input.addEventListener('blur', run);
        run();
    }

    function bindBankDetailsValidation(form) {
        form.querySelectorAll('input[name$="account_name"]').forEach((input) => {
            bindLiveFieldValidation(input, validatePersonNameField);
        });
        form.querySelectorAll('input[name$="account_number"]').forEach((input) => {
            bindLiveFieldValidation(input, validateAccountNumberField);
        });
        form.querySelectorAll('input[name$="ifsc_code"]').forEach((input) => {
            bindLiveFieldValidation(input, validateIfscField);
        });
        form.querySelectorAll('input[name$="bank_name"]').forEach((input) => {
            bindLiveFieldValidation(input, validateTitleField);
        });
    }

    function bindVehicleAccountType(form) {
        const vehicle = form.querySelector('[name="vehicle_no"]');
        const accountType = form.querySelector('[data-jb-bank-account-type], [name="account_type"]');
        if (!vehicle || !accountType) {
            return;
        }
        const sync = () => {
            const hasVehicle = vehicle.value.trim().length > 0;
            accountType.disabled = !hasVehicle;
            if (!hasVehicle) {
                accountType.value = '';
                clearFieldError(accountType);
            } else {
                validateBankAccountType(vehicle, accountType);
            }
        };
        const onTypeChange = () => validateBankAccountType(vehicle, accountType);
        vehicle.addEventListener('input', sync);
        accountType.addEventListener('change', onTypeChange);
        accountType.addEventListener('blur', onTypeChange);
        sync();
    }

    function bindAdminForm(form) {
        if (form.closest('.jb-login-form-panel, .jb-login-card')) {
            return;
        }
        form.querySelectorAll('[data-jb-max-chars]').forEach(bindCharCounter);
        form.querySelectorAll('[data-jb-restrict="gst"]').forEach(bindGstValidation);
        form.querySelectorAll('[data-jb-restrict="phone"]').forEach(bindPhoneValidation);
        form.querySelectorAll('input[type="file"][data-jb-max-mb]').forEach(bindFileSizeLimit);
        bindBankDetailsValidation(form);
        bindVehicleAccountType(form);

        form.addEventListener('submit', (event) => {
            let valid = true;
            form.querySelectorAll('[data-jb-restrict="gst"]').forEach((input) => {
                const value = input.value.trim();
                if (value !== '' && (!GST_PATTERN.test(value) || value.length !== 15)) {
                    showFieldError(input, 'Enter a valid 15-character GSTIN.');
                    valid = false;
                }
            });
            form.querySelectorAll('input[type="file"][data-jb-max-mb]').forEach((input) => {
                const file = input.files && input.files[0];
                if (file && file.size > MAX_IMAGE_BYTES) {
                    showFieldError(input, 'Image is too large. Maximum size is 4 MB.');
                    valid = false;
                }
            });
            form.querySelectorAll('[data-jb-restrict="phone"]').forEach((input) => {
                if (!validatePhoneValue(input)) {
                    valid = false;
                }
            });
            form.querySelectorAll('input[name$="account_name"]').forEach((input) => {
                if (!validatePersonNameField(input)) {
                    valid = false;
                }
            });
            form.querySelectorAll('input[name$="account_number"]').forEach((input) => {
                if (!validateAccountNumberField(input)) {
                    valid = false;
                }
            });
            form.querySelectorAll('input[name$="ifsc_code"]').forEach((input) => {
                if (!validateIfscField(input)) {
                    valid = false;
                }
            });
            form.querySelectorAll('input[name$="bank_name"]').forEach((input) => {
                if (!validateTitleField(input)) {
                    valid = false;
                }
            });
            const vehicle = form.querySelector('[name="vehicle_no"]');
            const accountType = form.querySelector('[data-jb-bank-account-type], [name="account_type"]');
            if (!validateBankAccountType(vehicle, accountType)) {
                valid = false;
            }
            form.querySelectorAll('[required]').forEach((input) => {
                if (input.disabled || input.type === 'file') {
                    return;
                }
                if (!String(input.value ?? '').trim()) {
                    valid = false;
                    showFieldError(input, 'This field is required.');
                }
            });
            if (!valid) {
                event.preventDefault();
            }
        });
    }

    function init(root) {
        root.querySelectorAll('[data-jb-restrict]').forEach(bindRestriction);
        root.querySelectorAll('form[method="POST"], form[method="post"]').forEach(bindAdminForm);
    }

    const SIDEBAR_SCROLL_KEY = 'jb-admin-sidebar-scroll';

    function initSidebarScrollPersistence() {
        const nav = document.getElementById('jb-sidebar-nav');
        if (!nav) {
            return;
        }

        const saveScroll = () => {
            sessionStorage.setItem(SIDEBAR_SCROLL_KEY, String(nav.scrollTop));
        };

        const restoreScroll = () => {
            const saved = sessionStorage.getItem(SIDEBAR_SCROLL_KEY);
            if (saved === null) {
                return;
            }
            const top = parseInt(saved, 10);
            if (!Number.isNaN(top) && top >= 0) {
                nav.scrollTop = top;
            }
        };

        restoreScroll();
        requestAnimationFrame(restoreScroll);

        nav.addEventListener(
            'click',
            (event) => {
                if (event.target.closest('a.jb-nav-link')) {
                    saveScroll();
                }
            },
            true
        );

        let scrollTimer;
        nav.addEventListener(
            'scroll',
            () => {
                clearTimeout(scrollTimer);
                scrollTimer = setTimeout(saveScroll, 80);
            },
            { passive: true }
        );

        window.addEventListener('beforeunload', saveScroll);
    }

    document.addEventListener('DOMContentLoaded', () => {
        initSidebarScrollPersistence();
        init(document);
    });
})();
