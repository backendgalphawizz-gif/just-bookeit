/**
 * Live input filtering for admin forms — blocks invalid characters as the user types.
 */
const JB_FILTERS = {
    'person-name': (value) => value.replace(/[^\p{L}\s.'-]/gu, ''),
    city: (value) => value.replace(/[^\p{L}\s.'-]/gu, ''),
    phone: (value) => value.replace(/\D/g, '').slice(0, 10),
    'account-number': (value) => value.replace(/\D/g, '').slice(0, 20),
    ifsc: (value) => value.replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, 11),
    title: (value) => value.replace(/[^\p{L}\p{N}\s.,'&()\-]/gu, ''),
    text: (value) => value.replace(/[^\p{L}\p{N}\s.,'!?&()\-:@#%/\\[\]\n\r]/gu, ''),
    integer: (value) => value.replace(/\D/g, ''),
    decimal: (value) => {
        let cleaned = value.replace(/[^\d.]/g, '');
        const parts = cleaned.split('.');
        if (parts.length > 2) {
            cleaned = `${parts[0]}.${parts.slice(1).join('')}`;
        }
        return cleaned;
    },
    currency: (value) => value.replace(/[^A-Za-z]/g, '').toUpperCase().slice(0, 10),
    'comma-list': (value) => value.replace(/[^\p{L}\p{N}\s,.\-]/gu, ''),
    gst: (value) => value.replace(/[^0-9A-Za-z]/g, '').toUpperCase().slice(0, 15),
    'vehicle-no': (value) => value.replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, 20),
    email: (value) => value.replace(/[^\w.@+\-]/g, '').slice(0, 255),
    url: (value) => value.replace(/[^\w.:\/?#@!$&'()*+,;=%\-\[\]]/g, '').slice(0, 500),
};

function filterValue(type, value) {
    const fn = JB_FILTERS[type];
    return fn ? fn(value) : value;
}

function bindRestriction(input) {
    const type = input.dataset.jbRestrict;
    if (!type || !JB_FILTERS[type]) {
        return;
    }

    const apply = () => {
        const start = input.selectionStart;
        const end = input.selectionEnd;
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

    input.addEventListener('drop', (event) => {
        event.preventDefault();
    });

    if (input.value) {
        input.value = filterValue(type, input.value);
    }
}

export function initAdminInputRestrictions(root = document) {
    root.querySelectorAll('[data-jb-restrict]').forEach(bindRestriction);
}

document.addEventListener('DOMContentLoaded', () => initAdminInputRestrictions());
