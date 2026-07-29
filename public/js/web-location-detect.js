(function () {
    var root = document.getElementById('jbw-location-picker');
    if (!root) {
        return;
    }

    var detectUrl = root.getAttribute('data-detect-url');
    var autoDetect = root.getAttribute('data-auto-detect') === '1';
    var labelEl = root.querySelector('[data-location-label]');
    var detectBtn = root.querySelector('[data-location-detect]');
    var statusEl = root.querySelector('[data-location-detect-status]');
    var busy = false;
    var hadLabel = labelEl && labelEl.textContent && labelEl.textContent.trim() !== ''
        && labelEl.textContent.trim() !== 'Choose location'
        && labelEl.textContent.trim() !== 'Detecting location…';
    var previousLabel = hadLabel ? labelEl.textContent.trim() : '';

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function setStatus(text) {
        if (statusEl) {
            statusEl.textContent = text;
        }
    }

    function setLabel(text) {
        if (labelEl) {
            labelEl.textContent = text;
        }

        document.querySelectorAll('.jbw-mnav-link-text small').forEach(function (el) {
            el.textContent = text;
        });
    }

    function postDetect(payload) {
        return fetch(detectUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload || {}),
        }).then(function (response) {
            return response.json().then(function (data) {
                return { ok: response.ok, data: data };
            }).catch(function () {
                return { ok: false, data: null };
            });
        });
    }

    function applyResult(result, fallbackStatus, options) {
        busy = false;
        options = options || {};

        if (!result.ok || !result.data || !result.data.ok) {
            setStatus((result.data && result.data.message) || fallbackStatus || 'Could not detect location');
            if (!previousLabel) {
                setLabel('Choose location');
            } else {
                setLabel(previousLabel);
            }
            return;
        }

        var via = result.data.source === 'ip' ? ' (approx.)' : '';
        setStatus('Using ' + (result.data.city || result.data.label) + via);
        setLabel(result.data.label);

        // Reload once so catalog/home filters use the new location.
        if (!options.skipReload) {
            window.location.reload();
        }
    }

    function detectByIp(fallbackStatus) {
        setStatus('Detecting from network…');
        if (!previousLabel) {
            setLabel('Detecting location…');
        }

        return postDetect({ gps_attempted: true })
            .then(function (result) {
                applyResult(result, fallbackStatus || 'Could not detect location');
            })
            .catch(function () {
                busy = false;
                setStatus(fallbackStatus || 'Could not detect location');
                setLabel(previousLabel || 'Choose location');
            });
    }

    function gpsErrorMessage(error) {
        if (!error) {
            return 'Location unavailable — trying network…';
        }

        if (error.code === 1) {
            return 'Permission denied — trying network…';
        }

        if (error.code === 2) {
            return 'GPS unavailable — trying network…';
        }

        if (error.code === 3) {
            return 'Location timed out — trying network…';
        }

        return 'Location unavailable — trying network…';
    }

    function detect(options) {
        options = options || {};

        if (!detectUrl || busy) {
            return;
        }

        busy = true;
        setStatus('Detecting your location…');
        if (!previousLabel) {
            setLabel('Detecting location…');
        }

        // Secure contexts only (HTTPS / localhost). LAN HTTP cannot use GPS.
        var canUseGps = typeof navigator !== 'undefined'
            && navigator.geolocation
            && (window.isSecureContext || location.hostname === 'localhost' || location.hostname === '127.0.0.1');

        if (!canUseGps) {
            detectByIp('Using network location');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            function (position) {
        // Also mark GPS attempted when posting GPS coords (even if city match fails later).
        postDetect({
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            gps_attempted: true,
        })
                    .then(function (result) {
                        if (result.ok && result.data && result.data.ok) {
                            applyResult(result, null, options);
                            return;
                        }

                        setStatus((result.data && result.data.message) || 'GPS city not matched — trying network…');
                        return detectByIp('Could not detect location');
                    })
                    .catch(function () {
                        return detectByIp('Could not detect location');
                    });
            },
            function (error) {
                setStatus(gpsErrorMessage(error));
                // Mark GPS attempted via IP fallback request (no coords) path —
                // also fire a lightweight GPS-mark by posting empty after fail
                // so server can still set session flag through IP detect.
                detectByIp('Could not detect location');
            },
            {
                enableHighAccuracy: true,
                timeout: 12000,
                maximumAge: 60000,
            }
        );
    }

    if (detectBtn) {
        detectBtn.addEventListener('click', function () {
            detect({ skipReload: false });
        });
    }

    // On first visit (or IP-only location), ask the browser for current GPS.
    if (autoDetect) {
        // Slight delay so the header paints first, then permission prompt.
        window.setTimeout(function () {
            detect();
        }, 250);
    }
})();
