/**
 * Chat compose: Enter sends, Shift+Enter adds a new line.
 * Attach forms with data-chat-compose and textareas with data-chat-input.
 */
(function () {
    function bindComposeForm(form) {
        const textarea = form.querySelector('[data-chat-input]');

        if (!textarea || textarea.dataset.chatComposeBound === '1') {
            return;
        }

        textarea.dataset.chatComposeBound = '1';

        textarea.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter' || event.shiftKey) {
                return;
            }

            event.preventDefault();

            const hasText = textarea.value.trim().length > 0;
            const fileInput = form.querySelector('input[type="file"][name="attachment"]');
            const hasFile = Boolean(fileInput && fileInput.files && fileInput.files.length > 0);

            if (hasText || hasFile) {
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            }
        });
    }

    function init() {
        document.querySelectorAll('[data-chat-compose]').forEach(bindComposeForm);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
