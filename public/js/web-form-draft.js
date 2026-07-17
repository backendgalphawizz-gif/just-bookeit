(function () {
    'use strict';

    function storageKey(form) {
        return form.getAttribute('data-draft-key') || '';
    }

    function collect(form) {
        var data = {};
        var elements = form.querySelectorAll('input[name], select[name], textarea[name]');

        elements.forEach(function (el) {
            var name = el.getAttribute('name');
            if (!name || name === '_token') return;

            if (el.type === 'checkbox') {
                // Support both single checkboxes and indexed names (vendor_shipments[0][shipment_required]).
                if (Object.prototype.hasOwnProperty.call(data, name) && !Array.isArray(data[name])) {
                    data[name] = [data[name]];
                }
                if (Array.isArray(data[name])) {
                    if (el.checked) data[name].push(el.value || '1');
                } else {
                    data[name] = el.checked ? (el.value || '1') : '';
                }
                return;
            }

            if (el.type === 'radio') {
                if (el.checked) data[name] = el.value;
                return;
            }

            data[name] = el.value;
        });

        return data;
    }

    function apply(form, data) {
        if (!data || typeof data !== 'object') return;

        Object.keys(data).forEach(function (name) {
            var value = data[name];
            var fields = form.querySelectorAll('[name="' + CSS.escape(name) + '"]');

            if (!fields.length) {
                // Fallback for names that CSS.escape might struggle with in older browsers —
                // query by iterating when needed.
                fields = Array.prototype.filter.call(
                    form.querySelectorAll('input[name], select[name], textarea[name]'),
                    function (el) { return el.getAttribute('name') === name; }
                );
            }

            fields.forEach(function (el) {
                if (el.type === 'checkbox') {
                    if (Array.isArray(value)) {
                        el.checked = value.indexOf(el.value || '1') !== -1;
                    } else {
                        el.checked = value === (el.value || '1') || value === true || value === '1' || value === 1;
                    }
                    return;
                }

                if (el.type === 'radio') {
                    el.checked = el.value === String(value);
                    return;
                }

                el.value = value == null ? '' : value;
            });
        });
    }

    function save(form) {
        var key = storageKey(form);
        if (!key) return;

        try {
            sessionStorage.setItem(key, JSON.stringify(collect(form)));
        } catch (e) { /* quota / private mode */ }
    }

    function restore(form) {
        var key = storageKey(form);
        if (!key) return;

        // Prefer Laravel old() flash (validation errors) over a stored draft.
        if (form.getAttribute('data-has-old') === '1') return;

        try {
            var raw = sessionStorage.getItem(key);
            if (!raw) return;
            apply(form, JSON.parse(raw));
        } catch (e) { /* ignore corrupt draft */ }
    }

    function clear(form) {
        var key = storageKey(form);
        if (!key) return;
        try { sessionStorage.removeItem(key); } catch (e) { /* ignore */ }
    }

    function bind(form) {
        if (!storageKey(form)) return;

        restore(form);

        form.addEventListener('input', function () { save(form); });
        form.addEventListener('change', function () { save(form); });

        // Save right before leaving for measurements / addresses / etc.
        form.querySelectorAll('a[data-save-draft], a[href*="measurements"]').forEach(function (link) {
            link.addEventListener('click', function () { save(form); });
        });

        form.addEventListener('submit', function () { clear(form); });

        // Notify page scripts that draft was restored (e.g. refresh pricing preview).
        form.dispatchEvent(new CustomEvent('jbw:draft-restored', { bubbles: true }));
    }

    function init() {
        document.querySelectorAll('form[data-draft-key]').forEach(bind);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.JbwFormDraft = { save: save, restore: restore, clear: clear };
})();
