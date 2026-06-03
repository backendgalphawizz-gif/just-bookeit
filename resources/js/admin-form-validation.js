const GST_PATTERN = /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/;
const MAX_IMAGE_BYTES = 4 * 1024 * 1024;

function bindCharCounter(input) {
    const max = parseInt(input.dataset.jbMaxChars, 10);
    if (!max) {
        return;
    }

    const counter = document.querySelector(`[data-jb-char-count-for="${input.id}"]`);
    if (!counter) {
        return;
    }

    const update = () => {
        const length = input.value.length;
        counter.textContent = `${length}/${max}`;
        counter.classList.toggle('text-rose-600', length > max);
        counter.classList.toggle('font-semibold', length > max);
    };

    input.addEventListener('input', update);
    update();
}

function showFieldError(input, message) {
    let el = input.parentElement?.querySelector('[data-jb-live-error]');
    if (!el) {
        el = document.createElement('p');
        el.dataset.jbLiveError = '1';
        el.className = 'mt-1.5 text-xs font-medium text-rose-600';
        input.parentElement?.appendChild(el);
    }
    el.textContent = message;
    input.classList.add('border-rose-400', 'ring-rose-200');
}

function clearFieldError(input) {
    const el = input.parentElement?.querySelector('[data-jb-live-error]');
    if (el) {
        el.remove();
    }
    input.classList.remove('border-rose-400', 'ring-rose-200');
}

function bindGstValidation(input) {
    const validate = () => {
        const value = input.value.trim().toUpperCase();
        if (value === '') {
            clearFieldError(input);
            return true;
        }
        if (value.length < 15) {
            showFieldError(input, `GST must be 15 characters (${value.length}/15).`);
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

function bindFileSizeLimit(input) {
    const maxMb = parseFloat(input.dataset.jbMaxMb || '4');
    const maxBytes = maxMb * 1024 * 1024;

    input.addEventListener('change', () => {
        const file = input.files?.[0];
        if (!file) {
            clearFieldError(input);
            return;
        }
        if (file.size > maxBytes) {
            showFieldError(input, `File must be ${maxMb} MB or smaller (selected ${(file.size / (1024 * 1024)).toFixed(1)} MB).`);
            input.value = '';
            input.dispatchEvent(new Event('change', { bubbles: true }));
            return;
        }
        clearFieldError(input);
    });
}

function bindVehicleAccountType(form) {
    const vehicle = form.querySelector('[name="vehicle_no"]');
    const accountType = form.querySelector('[name="account_type"]');
    if (!vehicle || !accountType) {
        return;
    }

    const sync = () => {
        const hasVehicle = vehicle.value.trim().length > 0;
        accountType.disabled = !hasVehicle;
        if (!hasVehicle) {
            accountType.value = '';
        }
    };

    vehicle.addEventListener('input', sync);
    sync();
}

function bindAdminForm(form) {
    form.querySelectorAll('[data-jb-max-chars]').forEach(bindCharCounter);
    form.querySelectorAll('[data-jb-restrict="gst"]').forEach(bindGstValidation);
    form.querySelectorAll('input[type="file"][data-jb-max-mb]').forEach(bindFileSizeLimit);
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
            const file = input.files?.[0];
            if (file && file.size > MAX_IMAGE_BYTES) {
                showFieldError(input, 'File must be 4 MB or smaller.');
                valid = false;
            }
        });

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

export function initAdminFormValidation(root = document) {
    root.querySelectorAll('form[method="POST"], form[method="post"]').forEach((form) => {
        if (form.closest('.jb-login-form-panel, .jb-login-card')) {
            return;
        }
        bindAdminForm(form);
    });
}

document.addEventListener('DOMContentLoaded', () => initAdminFormValidation());
