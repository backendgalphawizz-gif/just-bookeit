/**
 * Shared image lightbox for admin and vendor panels.
 * Add class "panel-lightbox-trigger" to any content image to enable click-to-zoom.
 */
(function () {
    const OPEN_CLASS = 'panel-lightbox-open';
    let overlay = null;

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function resolveSrc(img) {
        const custom = img.dataset.lightboxSrc;
        if (custom) {
            return custom;
        }

        const src = img.currentSrc || img.src;
        if (!src || src === window.location.href) {
            return '';
        }

        return src;
    }

    function close() {
        if (!overlay) {
            return;
        }

        overlay.remove();
        overlay = null;
        document.body.classList.remove(OPEN_CLASS);
        document.removeEventListener('keydown', onKeydown);
    }

    function onKeydown(event) {
        if (event.key === 'Escape') {
            close();
        }
    }

    function open(src, alt) {
        close();

        overlay = document.createElement('div');
        overlay.className = 'panel-lightbox-overlay';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-label', alt || 'Image preview');

        const caption = alt
            ? `<p class="panel-lightbox-caption">${escapeHtml(alt)}</p>`
            : '';

        overlay.innerHTML = `
            <button type="button" class="panel-lightbox-close" aria-label="Close preview">&times;</button>
            <div class="panel-lightbox-backdrop"></div>
            <div class="panel-lightbox-stage">
                <img src="${escapeHtml(src)}" alt="${escapeHtml(alt || '')}" class="panel-lightbox-img">
                ${caption}
            </div>
        `;

        overlay.querySelector('.panel-lightbox-backdrop').addEventListener('click', close);
        overlay.querySelector('.panel-lightbox-close').addEventListener('click', close);
        overlay.querySelector('.panel-lightbox-img').addEventListener('click', (event) => {
            event.stopPropagation();
        });

        document.body.classList.add(OPEN_CLASS);
        document.body.appendChild(overlay);
        document.addEventListener('keydown', onKeydown);
        overlay.querySelector('.panel-lightbox-close').focus();
    }

    document.addEventListener('click', (event) => {
        const img = event.target.closest('img.panel-lightbox-trigger');
        if (!img) {
            return;
        }

        const src = resolveSrc(img);
        if (!src) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        open(src, img.alt || img.getAttribute('title') || '');
    });
})();
