(function () {
    'use strict';

    // Inline SVG placeholder (never 404s). Pages can override per-image via data-fallback="...".
    var DEFAULT_FALLBACK =
        'data:image/svg+xml;charset=UTF-8,' +
        encodeURIComponent(
            '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="600" viewBox="0 0 600 600">' +
            '<rect width="600" height="600" fill="#f1f2f4"/>' +
            '<g fill="none" stroke="#c3c8cf" stroke-width="14" stroke-linecap="round" stroke-linejoin="round">' +
            '<rect x="170" y="190" width="260" height="220" rx="18"/>' +
            '<circle cx="245" cy="262" r="26"/>' +
            '<path d="M186 380l82-78 60 54 46-40 40 36"/>' +
            '</g>' +
            '<text x="300" y="470" text-anchor="middle" font-family="Arial, sans-serif" font-size="30" fill="#9aa3ad">Image unavailable</text>' +
            '</svg>'
        );

    function resolveFallback(img) {
        var custom = img.getAttribute('data-fallback');
        return (custom && custom.trim() !== '') ? custom : DEFAULT_FALLBACK;
    }

    function handleError(img) {
        if (!img || img.tagName !== 'IMG') return;
        // Prevent infinite loop if the fallback itself fails.
        if (img.dataset.fallbackApplied === '1') return;
        img.dataset.fallbackApplied = '1';
        img.src = resolveFallback(img);
    }

    // Capture phase: image error events do not bubble, but they can be captured.
    document.addEventListener(
        'error',
        function (event) {
            var target = event.target;
            if (target && target.tagName === 'IMG') {
                handleError(target);
            }
        },
        true
    );

    // Catch images that already failed before this script ran.
    function sweep() {
        var imgs = document.getElementsByTagName('img');
        for (var i = 0; i < imgs.length; i++) {
            var img = imgs[i];
            if (img.complete && img.naturalWidth === 0 && img.dataset.fallbackApplied !== '1') {
                handleError(img);
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', sweep);
    } else {
        sweep();
    }
})();
