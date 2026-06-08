/**
 * Quill rich text editors for admin legal / policy fields.
 */
(function () {
    const TOOLBAR = [
        [{ header: [1, 2, 3, false] }],
        ['bold', 'italic', 'underline'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['link'],
        ['clean'],
    ];

    function syncInput(quill, input) {
        const html = quill.root.innerHTML.trim();
        const empty = html === '' || html === '<p><br></p>' || html === '<p></p>';
        input.value = empty ? '' : html;
    }

    function initField(wrap) {
        if (wrap.dataset.jbQuillReady === '1' || typeof Quill === 'undefined') {
            return;
        }

        const input = wrap.querySelector('[data-jb-quill-input]');
        const mount = wrap.querySelector('.jb-quill-mount');

        if (!input || !mount) {
            return;
        }

        const quill = new Quill(mount, {
            theme: 'snow',
            modules: { toolbar: TOOLBAR },
        });

        const initial = input.value.trim();
        if (initial) {
            quill.clipboard.dangerouslyPasteHTML(initial);
        }

        const sync = () => syncInput(quill, input);
        quill.on('text-change', sync);

        const form = wrap.closest('form');
        if (form) {
            form.addEventListener('submit', sync);
        }

        wrap.dataset.jbQuillReady = '1';
    }

    function init(root) {
        root.querySelectorAll('[data-jb-quill-field]').forEach(initField);
    }

    document.addEventListener('DOMContentLoaded', () => init(document));
})();
