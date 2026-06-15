/**
 * Admin panel scripts — no build step required. Loaded directly from public/js.
 */
(function () {
    const GST_PATTERN = /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/;
    const IFSC_PATTERN = /^[A-Z]{4}0[A-Z0-9]{6}$/;
    const ALLOWED_EMAIL_TLDS = [
        'co.in', 'co.uk', 'com.au', 'ac.in', 'edu.in', 'gov.in', 'net.in', 'org.in', 'nic.in', 'res.in', 'gen.in',
        'com', 'in', 'org', 'net', 'edu', 'gov', 'io', 'co', 'uk', 'us', 'au', 'ca', 'de', 'fr', 'info', 'biz',
        'me', 'app', 'dev', 'ai', 'xyz', 'pro', 'int', 'mil',
    ].sort((a, b) => b.length - a.length);

    const EMAIL_TLD_PATTERN = ALLOWED_EMAIL_TLDS.map((tld) => tld.replace(/\./g, '\\.')).join('|');
    const EMAIL_HTML_PATTERN = '^(?!\\.)(?!.*\\.\\.)[a-zA-Z0-9._%+\\-]+(?<!\\.)@(?:[a-zA-Z0-9](?:[a-zA-Z0-9\\-]*[a-zA-Z0-9])?\\.)+(?:' + EMAIL_TLD_PATTERN + ')$';
    const EMAIL_PATTERN = new RegExp(EMAIL_HTML_PATTERN, 'i');
    const EMAIL_MESSAGE = 'Enter a valid email ID ending with .com, .in, .org, or another recognised domain (e.g. name@gmail.com).';
    const PERSON_NAME_PATTERN = /^[\p{L}\s.'-]*$/u;
    const TITLE_PATTERN = /^[\p{L}\p{N}\s.,'&()\-]*$/u;
    const MAX_IMAGE_BYTES = 4 * 1024 * 1024;
    const DEFAULT_SAFE_TOTAL_BYTES = 7 * 1024 * 1024;

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
        email: (value) => {
            let cleaned = value.replace(/\s/g, '').replace(/[^\w.@+\-]/g, '');
            const firstAt = cleaned.indexOf('@');
            if (firstAt !== -1) {
                cleaned = cleaned.slice(0, firstAt + 1) + cleaned.slice(firstAt + 1).replace(/@/g, '');
            }
            return cleaned.slice(0, 255);
        },
        'login-or-username': (value) => value.replace(/\s/g, '').slice(0, 255),
        url: (value) => value.replace(/[^\w.:\/?#@!$&'()*+,;=%\-\[\]]/g, '').slice(0, 500),
    };

    function filterValue(type, value) {
        return JB_FILTERS[type] ? JB_FILTERS[type](value) : value;
    }

    function maxLengthFor(input) {
        const fromData = parseInt(input.dataset.jbMaxChars, 10);
        if (fromData > 0) {
            return fromData;
        }
        const fromAttr = parseInt(input.getAttribute('maxlength'), 10);
        return fromAttr > 0 ? fromAttr : null;
    }

    function clampToMaxLength(input, value) {
        const max = maxLengthFor(input);
        return max ? value.slice(0, max) : value;
    }

    function bindRestriction(input) {
        const type = input.dataset.jbRestrict;
        if (!type || !JB_FILTERS[type]) {
            return;
        }

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
        input.addEventListener('paste', (event) => {
            event.preventDefault();
            const pasted = (event.clipboardData || window.clipboardData)?.getData('text') ?? '';
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
        input.addEventListener('drop', (event) => event.preventDefault());
        if (input.value) {
            input.value = clampToMaxLength(input, filterValue(type, input.value));
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

    function hasValidEmailTld(domain) {
        if (!domain || !domain.includes('.')) {
            return false;
        }
        const lowerDomain = domain.toLowerCase();
        return ALLOWED_EMAIL_TLDS.some((tld) => lowerDomain.endsWith('.' + tld));
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
        const atCount = (value.match(/@/g) || []).length;
        if (atCount !== 1) {
            showFieldError(input, EMAIL_MESSAGE);
            return false;
        }
        const parts = value.split('@');
        if (!parts[0] || !parts[1] || !hasValidEmailTld(parts[1])) {
            showFieldError(input, EMAIL_MESSAGE);
            return false;
        }
        if (!EMAIL_PATTERN.test(value)) {
            showFieldError(input, EMAIL_MESSAGE);
            return false;
        }
        clearFieldError(input);
        return true;
    }

    function validateLoginField(input) {
        const value = input.value.trim();
        if (value === '') {
            showFieldError(input, 'This field is required.');
            return false;
        }
        if (value.includes('@') && !validateEmailField(input)) {
            return false;
        }
        clearFieldError(input);
        return true;
    }

    function bindEmailValidation(input) {
        const validate = () => validateEmailField(input);
        input.addEventListener('input', validate);
        input.addEventListener('blur', validate);
    }

    function bindLoginValidation(input) {
        const validate = () => validateLoginField(input);
        input.addEventListener('input', validate);
        input.addEventListener('blur', validate);
    }

    function ensureEmailInputs(form) {
        form.querySelectorAll('[data-jb-restrict="email"], input[type="email"]').forEach((input) => {
            if (!input.dataset.jbRestrict) {
                input.dataset.jbRestrict = 'email';
            }
            if (!input.getAttribute('pattern')) {
                input.setAttribute('pattern', EMAIL_HTML_PATTERN);
            }
            if (!input.getAttribute('title')) {
                input.setAttribute('title', EMAIL_MESSAGE);
            }
            bindRestriction(input);
            bindEmailValidation(input);
        });
    }

    function readUploadLimits() {
        const postMeta = document.querySelector('meta[name="jb-post-max-bytes"]');
        const perFileMeta = document.querySelector('meta[name="jb-per-file-max-bytes"]');
        const safeTotal = postMeta ? parseInt(postMeta.content, 10) : DEFAULT_SAFE_TOTAL_BYTES;
        const perFileMax = perFileMeta ? parseInt(perFileMeta.content, 10) : MAX_IMAGE_BYTES;

        return {
            safeTotal: Number.isFinite(safeTotal) && safeTotal > 0 ? safeTotal : DEFAULT_SAFE_TOTAL_BYTES,
            perFileMax: Number.isFinite(perFileMax) && perFileMax > 0 ? perFileMax : MAX_IMAGE_BYTES,
        };
    }

    function formatUploadMb(bytes) {
        return (bytes / (1024 * 1024)).toFixed(1);
    }

    function filesFromInput(input) {
        return input.files ? Array.from(input.files) : [];
    }

    function maxBytesForFileInput(input) {
        const maxMb = parseFloat(input.dataset.jbMaxMb || '4');
        return maxMb * 1024 * 1024;
    }

    function showFormUploadBanner(form, message) {
        let banner = form.querySelector('[data-jb-form-upload-error]');
        if (!banner) {
            banner = document.createElement('div');
            banner.dataset.jbFormUploadError = '1';
            banner.className = 'jb-upload-hint-alert mb-4';
            banner.setAttribute('role', 'alert');
            form.insertBefore(banner, form.firstChild);
        }
        banner.textContent = message;
        banner.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function clearFormUploadBanner(form) {
        form.querySelector('[data-jb-form-upload-error]')?.remove();
    }

    function validateFormUploads(form) {
        const limits = readUploadLimits();
        let totalBytes = 0;
        let blocked = false;

        clearFormUploadBanner(form);

        form.querySelectorAll('input[type="file"]').forEach((input) => {
            const maxBytes = maxBytesForFileInput(input);
            const maxMb = maxBytes / (1024 * 1024);

            filesFromInput(input).forEach((file) => {
                totalBytes += file.size;
                if (file.size > maxBytes) {
                    showFieldError(input, 'One of the images is too large. Please choose a smaller photo.');
                    input.value = '';
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                    blocked = true;
                }
            });
        });

        if (blocked) {
            return false;
        }

        if (totalBytes > limits.safeTotal) {
            showFormUploadBanner(form, 'Too many images selected. Remove a few photos and try again.');
            return false;
        }

        return true;
    }

    function bindFileSizeLimit(input) {
        if (input.dataset.jbFileAlpine) {
            return;
        }
        const maxBytes = maxBytesForFileInput(input);
        const maxMb = maxBytes / (1024 * 1024);

        input.addEventListener('change', () => {
            const files = filesFromInput(input);
            if (!files.length) {
                clearFieldError(input);
                return;
            }

            for (const file of files) {
                if (file.size > maxBytes) {
                    showFieldError(input, 'One of the images is too large. Please choose a smaller photo.');
                    input.value = '';
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                    return;
                }
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

    function bindLoginForm(form) {
        form.querySelectorAll('[data-jb-restrict="login-or-username"]').forEach(bindLoginValidation);

        form.addEventListener('submit', (event) => {
            let valid = true;
            form.querySelectorAll('[data-jb-restrict="login-or-username"]').forEach((input) => {
                if (!validateLoginField(input)) {
                    valid = false;
                }
            });
            if (!valid) {
                event.preventDefault();
            }
        });
    }

    function bindAdminForm(form) {
        if (form.closest('.jb-login-form-panel, .jb-login-card')) {
            return;
        }
        if (form.querySelector('[data-jb-quill-field]')) {
            return;
        }
        ensureEmailInputs(form);
        form.querySelectorAll('[data-jb-max-chars]').forEach(bindCharCounter);
        form.querySelectorAll('[data-jb-restrict="gst"]').forEach(bindGstValidation);
        form.querySelectorAll('[data-jb-restrict="phone"]').forEach(bindPhoneValidation);
        form.querySelectorAll('[data-jb-restrict="email"]').forEach(bindEmailValidation);
        form.querySelectorAll('input[type="file"][data-jb-max-mb]').forEach(bindFileSizeLimit);
        bindBankDetailsValidation(form);
        bindVehicleAccountType(form);

        form.addEventListener('submit', (event) => {
            let valid = true;

            if (!validateFormUploads(form)) {
                event.preventDefault();
                return;
            }

            form.querySelectorAll('[data-jb-restrict="gst"]').forEach((input) => {
                const value = input.value.trim();
                if (value !== '' && (!GST_PATTERN.test(value) || value.length !== 15)) {
                    showFieldError(input, 'Enter a valid 15-character GSTIN.');
                    valid = false;
                }
            });
            form.querySelectorAll('input[type="file"]').forEach((input) => {
                const maxBytes = maxBytesForFileInput(input);
                const maxMb = maxBytes / (1024 * 1024);

                filesFromInput(input).forEach((file) => {
                    if (file.size > maxBytes) {
                        showFieldError(input, 'One of the images is too large. Please choose a smaller photo.');
                        valid = false;
                    }
                });
            });
            form.querySelectorAll('[data-jb-restrict="phone"]').forEach((input) => {
                if (!validatePhoneValue(input)) {
                    valid = false;
                }
            });
            form.querySelectorAll('[data-jb-restrict="email"], input[type="email"]').forEach((input) => {
                if (!validateEmailField(input)) {
                    valid = false;
                }
            });
            form.querySelectorAll('[data-jb-max-chars]').forEach((input) => {
                const max = parseInt(input.dataset.jbMaxChars, 10);
                if (max && input.value.length > max) {
                    showFieldError(
                        input,
                        'Must be at most ' + max + ' characters (' + input.value.length + '/' + max + ').'
                    );
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
        root.querySelectorAll('form[method="POST"], form[method="post"]').forEach((form) => {
            if (form.classList.contains('jb-login-form')) {
                bindLoginForm(form);
                return;
            }
            bindAdminForm(form);
        });
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

    function isUsableAdminFilterDate(value, minDate, maxDate) {
        if (! value || ! /^\d{4}-\d{2}-\d{2}$/.test(value)) {
            return false;
        }

        return value >= minDate && value <= maxDate;
    }

    window.jbAdminFilterDateRange = (minDate, maxDate, initialFrom = '', initialTo = '') => ({
        minDate,
        maxDate,
        from: initialFrom,
        to: initialTo,
        isUsableDate(value) {
            return isUsableAdminFilterDate(value, this.minDate, this.maxDate);
        },
        maxForFrom() {
            if (this.isUsableDate(this.to)) {
                return this.to;
            }

            return this.maxDate;
        },
        minForTo() {
            if (this.isUsableDate(this.from)) {
                return this.from;
            }

            return this.minDate;
        },
        syncFrom(event) {
            const value = event.target.value;

            if (value === '' || this.isUsableDate(value)) {
                this.from = value;
            }
        },
        syncTo(event) {
            const value = event.target.value;

            if (value === '' || this.isUsableDate(value)) {
                this.to = value;
            }
        },
    });

    window.jbAdminFilterSingleDate = (minDate, maxDate) => ({
        minDate,
        maxDate,
    });

    window.jbCategoryTree = (categoryIds = []) => ({
        expanded: Object.fromEntries(categoryIds.map((id) => [id, true])),
        toggle(id) {
            this.expanded[id] = ! this.expanded[id];
        },
        isOpen(id) {
            return !! this.expanded[id];
        },
    });

    document.addEventListener('DOMContentLoaded', () => {
        initSidebarScrollPersistence();
        init(document);
    });
})();
