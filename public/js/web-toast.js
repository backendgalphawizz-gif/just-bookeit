(function () {
    function dismissToast(toast) {
        if (!toast || toast.dataset.toastClosing === '1') {
            return;
        }

        toast.dataset.toastClosing = '1';
        toast.classList.add('is-leaving');

        window.setTimeout(() => {
            toast.remove();

            const stack = document.getElementById('jbw-toast-stack');
            if (stack && stack.children.length === 0) {
                stack.remove();
            }
        }, 320);
    }

    function initToasts() {
        document.querySelectorAll('[data-toast]').forEach((toast) => {
            const closeButton = toast.querySelector('.jbw-toast-close');

            if (closeButton) {
                closeButton.addEventListener('click', () => dismissToast(toast));
            }

            window.setTimeout(() => dismissToast(toast), 4500);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initToasts);
    } else {
        initToasts();
    }
})();
