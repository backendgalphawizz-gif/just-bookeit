/**
 * Vendor panel — live input restrictions (same rules as admin panel).
 */
(function () {
    const ALLOWED_EMAIL_TLDS = [
        'co.in', 'co.uk', 'com.au', 'ac.in', 'edu.in', 'gov.in', 'net.in', 'org.in', 'nic.in', 'res.in', 'gen.in',
        'com', 'in', 'org', 'net', 'edu', 'gov', 'io', 'co', 'uk', 'us', 'au', 'ca', 'de', 'fr', 'info', 'biz',
        'me', 'app', 'dev', 'ai', 'xyz', 'pro', 'int', 'mil',
    ].sort((a, b) => b.length - a.length);

    const EMAIL_TLD_PATTERN = ALLOWED_EMAIL_TLDS.map((tld) => tld.replace(/\./g, '\\.')).join('|');
    const EMAIL_HTML_PATTERN = '^(?!\\.)(?!.*\\.\\.)[a-zA-Z0-9._%+\\-]+(?<!\\.)@(?:[a-zA-Z0-9](?:[a-zA-Z0-9\\-]*[a-zA-Z0-9])?\\.)+(?:' + EMAIL_TLD_PATTERN + ')$';
    const EMAIL_PATTERN = new RegExp(EMAIL_HTML_PATTERN, 'i');
    const EMAIL_MESSAGE = 'Enter a valid email ID ending with .com, .in, .org, or another recognised domain (e.g. name@gmail.com).';

    const VP_FILTERS = {
        'person-name': (v) => v.replace(/[^\p{L}\s.'-]/gu, ''),
        city: (v) => v.replace(/[^\p{L}\s.'-]/gu, ''),
        phone: (v) => v.replace(/\D/g, '').slice(0, 10),
        title: (v) => v.replace(/[^\p{L}\p{N}\s.,'&()\-]/gu, ''),
        text: (v) => v.replace(/[^\p{L}\p{N}\s.,'!?&()\-:@#%/\\[\]\n\r]/gu, ''),
        integer: (v) => v.replace(/\D/g, ''),
        gst: (v) => v.replace(/[^0-9A-Za-z]/g, '').toUpperCase().slice(0, 15),
        'account-number': (v) => v.replace(/\D/g, '').slice(0, 18),
        ifsc: (v) => v.replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, 11),
        email: (v) => v.replace(/[^\w.@+\-]/g, '').slice(0, 255),
        otp: (v) => v.replace(/\D/g, '').slice(0, 4),
        // Money / non-negative decimals (strips minus and scientific-notation letters).
        amount: (v) => {
            let next = String(v ?? '').replace(/[^\d.]/g, '');
            const dot = next.indexOf('.');
            if (dot !== -1) {
                next = next.slice(0, dot + 1) + next.slice(dot + 1).replace(/\./g, '');
            }
            return next;
        },
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
        const domain = value.split('@')[1] || '';
        const lowerDomain = domain.toLowerCase();
        const hasAllowedTld = ALLOWED_EMAIL_TLDS.some((tld) => lowerDomain.endsWith('.' + tld));
        if (!EMAIL_PATTERN.test(value) || !hasAllowedTld) {
            showFieldError(input, EMAIL_MESSAGE);
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
                if (input.type !== 'number') {
                    const delta = after.length - before.length;
                    const pos = Math.max(0, (start ?? after.length) + delta);
                    try {
                        input.setSelectionRange(pos, pos);
                    } catch (_) {}
                }
            }

            if (type === 'amount' && input.value !== '') {
                const num = Number(input.value);
                if (!Number.isFinite(num) || num < 0) {
                    input.value = '0';
                }
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
            try {
                input.setSelectionRange(pos, pos);
            } catch (_) {
                // type=number may not support selection ranges in some browsers.
            }
            input.dispatchEvent(new Event('input', { bubbles: true }));
        });

        if (type === 'amount') {
            const blockSignedKeys = (event) => {
                if (['-', '+', 'e', 'E'].includes(event.key)) {
                    event.preventDefault();
                }
            };
            input.addEventListener('keydown', blockSignedKeys);
            input.addEventListener('wheel', (event) => {
                if (document.activeElement === input) {
                    event.preventDefault();
                }
            }, { passive: false });
            input.addEventListener('change', () => {
                if (input.value === '') return;
                const num = Number(input.value);
                if (!Number.isFinite(num) || num < 0) {
                    input.value = '0';
                }
            });
        }

        if (input.value) input.value = clampToMaxLength(input, filterValue(type, input.value));

        if (type === 'email') {
            const validate = () => validateEmailField(input);
            input.addEventListener('input', validate);
            input.addEventListener('blur', validate);
        }
    }

    document.querySelectorAll('[data-vp-restrict]').forEach(bindRestriction);

    function countWords(value) {
        const trimmed = String(value || '').trim();
        if (!trimmed) {
            return 0;
        }

        return trimmed.split(/\s+/).filter(Boolean).length;
    }

    function clampToMaxWords(value, maxWords) {
        const text = String(value || '');
        if (!maxWords || maxWords < 1) {
            return text;
        }

        const trimmed = text.trim();
        if (!trimmed) {
            return text;
        }

        const words = trimmed.split(/\s+/).filter(Boolean);
        if (words.length <= maxWords) {
            return text;
        }

        return words.slice(0, maxWords).join(' ');
    }

    function bindWordLimiter(input) {
        const max = parseInt(input.dataset.vpMaxWords, 10);
        if (!max) {
            return;
        }

        const counter = document.querySelector('[data-vp-word-count-for="' + input.id + '"]')
            || document.querySelector('[data-vp-word-count-for="' + input.name + '"]');

        const update = () => {
            const clamped = clampToMaxWords(input.value, max);
            if (clamped !== input.value) {
                input.value = clamped;
            }
            const count = countWords(input.value);
            if (counter) {
                counter.textContent = count + '/' + max + ' words';
                counter.classList.toggle('is-limit', count >= max);
            }
        };

        input.addEventListener('input', update);
        input.addEventListener('paste', () => {
            requestAnimationFrame(update);
        });
        update();
    }

    document.querySelectorAll('[data-vp-max-words]').forEach(bindWordLimiter);

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
            form.querySelectorAll('[data-vp-max-words]').forEach((input) => {
                const max = parseInt(input.dataset.vpMaxWords, 10);
                const count = countWords(input.value);
                if (max && count > max) {
                    showFieldError(input, 'Must be at most ' + max + ' words (' + count + '/' + max + ').');
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

    function fileTooLargeMessage(file, maxBytes, label) {
        const selectedMb = (file.size / (1024 * 1024)).toFixed(1);
        const maxLabel = formatMaxSize(maxBytes);
        const name = file.name ? `"${file.name}"` : 'The selected file';
        return `${name} is too large (${selectedMb} MB). ${label} must be ${maxLabel} or smaller.`;
    }

    function validateFileInput(input, { alertOnError = false } = {}) {
        const maxBytes = parseInt(input.dataset.vpMaxFileBytes || String(VP_DEFAULT_MAX_FILE_BYTES), 10);
        const label = input.dataset.vpFileLabel || 'Each image';
        const files = input.files ? Array.from(input.files) : [];

        if (!files.length) {
            clearFieldError(input);
            return true;
        }

        for (const file of files) {
            if (file.size > maxBytes) {
                input.value = '';
                const message = fileTooLargeMessage(file, maxBytes, label);
                showFieldError(input, message);
                if (alertOnError) {
                    vpShowAlert({
                        type: 'warning',
                        title: 'Image too large',
                        message,
                    });
                }
                return false;
            }
        }

        clearFieldError(input);
        return true;
    }

    function bindFileInput(input) {
        input.addEventListener('change', () => {
            if (!validateFileInput(input, { alertOnError: true })) {
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

    document.querySelectorAll('[data-vp-product-form]').forEach((form) => {
        const isProductAmountField = (el) => {
            if (!(el instanceof HTMLInputElement) || el.type !== 'number') {
                return false;
            }
            if (el.dataset.vpRestrict === 'amount') {
                return true;
            }
            const name = el.getAttribute('name') || '';
            return /(^|\[)(price|price_per_day|advance_amount|advance)(\]|$)/i.test(name)
                || el.hasAttribute('data-vp-variant-price')
                || el.hasAttribute('data-vp-variant-advance');
        };

        form.addEventListener('keydown', (event) => {
            if (!isProductAmountField(event.target)) {
                return;
            }
            if (['-', '+', 'e', 'E'].includes(event.key)) {
                event.preventDefault();
            }
        });

        form.addEventListener('beforeinput', (event) => {
            if (!isProductAmountField(event.target)) {
                return;
            }
            if (typeof event.data === 'string' && /[+\-eE]/.test(event.data)) {
                event.preventDefault();
            }
        });

        form.addEventListener('input', (event) => {
            const input = event.target;
            if (!isProductAmountField(input)) {
                return;
            }
            if (String(input.value).includes('-')) {
                input.value = String(input.value).replace(/-/g, '');
            }
            if (input.value !== '' && Number(input.value) < 0) {
                input.value = '0';
            }
        });

        form.addEventListener('change', (event) => {
            const input = event.target;
            if (input instanceof HTMLInputElement && input.type === 'file' && form.contains(input)) {
                if (!input.dataset.vpMaxFileBytes) {
                    input.dataset.vpMaxFileBytes = String(VP_DEFAULT_MAX_FILE_BYTES);
                }
                validateFileInput(input, { alertOnError: true });
            }
        });

        form.addEventListener('submit', (event) => {
            form.querySelectorAll('input[type="number"]').forEach((input) => {
                if (!isProductAmountField(input)) {
                    return;
                }
                if (input.value !== '' && Number(input.value) < 0) {
                    input.value = '0';
                }
            });

            let valid = true;
            form.querySelectorAll('input[type="file"]').forEach((input) => {
                if (!input.dataset.vpMaxFileBytes) {
                    input.dataset.vpMaxFileBytes = String(VP_DEFAULT_MAX_FILE_BYTES);
                }
                if (!validateFileInput(input)) {
                    valid = false;
                }
            });
            if (!valid) {
                event.preventDefault();
                const firstInvalid = form.querySelector('[data-vp-live-error]');
                firstInvalid?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                vpShowAlert({
                    type: 'warning',
                    title: 'Image too large',
                    message: 'One or more images exceed the 20 MB limit. Choose smaller photos and try again.',
                });
            }
        });
    });

    // In-zone image/video previews for product dropzones + register upload tiles.
    (function initVendorMediaPreviews() {
        const isImageFile = (file) => {
            const type = String(file?.type || '').toLowerCase();
            if (type.startsWith('image/')) return true;
            const ext = String(file?.name || '').split('.').pop()?.toLowerCase() || '';
            return ['jpeg', 'jpg', 'png', 'webp', 'gif', 'bmp', 'svg', 'heic', 'heif', 'avif'].includes(ext);
        };

        const isVideoFile = (file) => {
            const type = String(file?.type || '').toLowerCase();
            if (type.startsWith('video/')) return true;
            const ext = String(file?.name || '').split('.').pop()?.toLowerCase() || '';
            return ['mp4', 'mov', 'm4v', 'webm', 'avi', 'mkv', '3gp', 'hevc', 'h265'].includes(ext);
        };

        const makeRemoveButton = (className, onClick) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = className;
            btn.title = 'Remove';
            btn.setAttribute('aria-label', 'Remove uploaded file');
            btn.textContent = '×';
            btn.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                onClick();
            });
            return btn;
        };

        const bindDropzone = (zone) => {
            if (zone.dataset.vpPreviewBound === '1') return;
            zone.dataset.vpPreviewBound = '1';

            const input = zone.querySelector('input[type="file"]');
            const nameEl = zone.querySelector('[data-vp-dropzone-name]');
            if (!input) return;

            let preview = zone.querySelector('[data-vp-dropzone-preview]');
            if (!preview) {
                preview = document.createElement('div');
                preview.className = 'vp-dropzone-preview';
                preview.dataset.vpDropzonePreview = '1';
                preview.hidden = true;
                zone.appendChild(preview);
            }

            let objectUrls = [];

            const revoke = () => {
                objectUrls.forEach((url) => URL.revokeObjectURL(url));
                objectUrls = [];
            };

            const setFiles = (files) => {
                try {
                    const dt = new DataTransfer();
                    Array.from(files || []).forEach((file) => dt.items.add(file));
                    input.files = dt.files;
                } catch (_) {
                    return;
                }
                input.dispatchEvent(new Event('change', { bubbles: true }));
            };

            const updateName = () => {
                if (!nameEl) return;
                const files = input.files;
                if (!files || files.length === 0) {
                    nameEl.textContent = zone.dataset.emptyText || 'No file chosen';
                    return;
                }
                if (files.length === 1) {
                    nameEl.textContent = files[0].name;
                    return;
                }
                nameEl.textContent = files.length + ' files selected';
            };

            const render = () => {
                revoke();
                preview.innerHTML = '';
                const files = Array.from(input.files || []);

                if (files.length === 0) {
                    zone.classList.remove('has-preview');
                    preview.hidden = true;
                    preview.classList.remove('vp-dropzone-preview--single', 'vp-dropzone-preview--multi');
                    updateName();
                    return;
                }

                zone.classList.add('has-preview');
                preview.hidden = false;
                preview.classList.toggle('vp-dropzone-preview--single', files.length === 1);
                preview.classList.toggle('vp-dropzone-preview--multi', files.length > 1);

                files.forEach((file, index) => {
                    const item = document.createElement('div');
                    item.className = 'vp-dropzone-preview-item';

                    if (isVideoFile(file)) {
                        const url = URL.createObjectURL(file);
                        objectUrls.push(url);
                        const video = document.createElement('video');
                        video.src = url;
                        video.muted = true;
                        video.playsInline = true;
                        video.preload = 'metadata';
                        video.title = 'Click to view';
                        video.addEventListener('click', (event) => {
                            event.preventDefault();
                            event.stopPropagation();
                            window.open(url, '_blank', 'noopener');
                        });
                        item.appendChild(video);
                    } else if (isImageFile(file)) {
                        const url = URL.createObjectURL(file);
                        objectUrls.push(url);
                        const img = document.createElement('img');
                        img.src = url;
                        img.alt = '';
                        img.className = 'panel-lightbox-trigger';
                        img.title = 'Click to view';
                        img.addEventListener('click', (event) => event.stopPropagation());
                        item.appendChild(img);
                    } else {
                        const label = document.createElement('div');
                        label.textContent = file.name;
                        label.style.cssText = 'padding:.75rem;font-size:.75rem;word-break:break-all;';
                        item.appendChild(label);
                    }

                    item.appendChild(makeRemoveButton('vp-dropzone-preview-remove', () => {
                        const next = files.filter((_, i) => i !== index);
                        setFiles(next);
                    }));
                    preview.appendChild(item);
                });

                updateName();
            };

            zone.addEventListener('click', (event) => {
                if (event.target === input) return;
                if (event.target.closest('.vp-dropzone-preview-remove')) return;
                if (event.target.closest('img.panel-lightbox-trigger, video')) return;
                input.click();
            });

            zone.addEventListener('dragover', (event) => {
                event.preventDefault();
                zone.classList.add('is-dragover');
            });
            zone.addEventListener('dragleave', () => zone.classList.remove('is-dragover'));
            zone.addEventListener('drop', (event) => {
                event.preventDefault();
                zone.classList.remove('is-dragover');
                if (!event.dataTransfer?.files?.length) return;
                const incoming = Array.from(event.dataTransfer.files);
                const selected = input.multiple ? incoming : incoming.slice(0, 1);
                setFiles(selected);
            });

            input.addEventListener('change', render);
            render();
        };

        const bindUploadTile = (input) => {
            if (input.dataset.vpPreviewBound === '1') return;
            input.dataset.vpPreviewBound = '1';

            const tile = input.closest('.vp-upload-tile');
            if (!tile) return;

            let preview = tile.querySelector('[data-vp-upload-tile-preview]');
            if (!preview) {
                preview = document.createElement('div');
                preview.className = 'vp-upload-tile-preview';
                preview.dataset.vpUploadTilePreview = '1';
                preview.hidden = true;
                tile.appendChild(preview);
            }

            const nameEl = tile.querySelector('.vp-upload-name');
            let objectUrl = null;

            const render = () => {
                if (objectUrl) {
                    URL.revokeObjectURL(objectUrl);
                    objectUrl = null;
                }
                preview.innerHTML = '';
                const file = input.files?.[0];

                if (!file) {
                    tile.classList.remove('has-preview');
                    preview.hidden = true;
                    if (nameEl) nameEl.textContent = '';
                    return;
                }

                tile.classList.add('has-preview');
                preview.hidden = false;
                if (nameEl) nameEl.textContent = file.name;

                if (isImageFile(file)) {
                    objectUrl = URL.createObjectURL(file);
                    const img = document.createElement('img');
                    img.src = objectUrl;
                    img.alt = '';
                    img.className = 'panel-lightbox-trigger';
                    preview.appendChild(img);
                } else if (isVideoFile(file)) {
                    objectUrl = URL.createObjectURL(file);
                    const video = document.createElement('video');
                    video.src = objectUrl;
                    video.muted = true;
                    video.playsInline = true;
                    video.preload = 'metadata';
                    preview.appendChild(video);
                }

                preview.appendChild(makeRemoveButton('vp-upload-tile-preview-remove', () => {
                    try {
                        input.value = '';
                    } catch (_) {}
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }));
            };

            input.addEventListener('change', render);
            render();
        };

        document.querySelectorAll('[data-vp-dropzone]').forEach(bindDropzone);
        document.querySelectorAll('input[type="file"][data-vp-preview]').forEach(bindUploadTile);
    })();
})();
