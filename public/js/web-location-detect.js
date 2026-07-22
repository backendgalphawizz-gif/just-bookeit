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

    function applyResult(result, fallbackStatus) {
        busy = false;

        if (!result.ok || !result.data || !result.data.ok) {
            setStatus((result.data && result.data.message) || fallbackStatus || 'Could not detect location');
            setLabel('Choose location');
            return;
        }

        var via = result.data.source === 'ip' ? ' (approx.)' : '';
        setStatus('Using ' + result.data.city + via);
        setLabel(result.data.label);
        window.location.reload();
    }

    function detectByIp(fallbackStatus) {
        setStatus('Detecting from network…');
        setLabel('Detecting location…');

        return postDetect({})
            .then(function (result) {
                applyResult(result, fallbackStatus || 'Could not detect location');
            })
            .catch(function () {
                busy = false;
                setStatus(fallbackStatus || 'Could not detect location');
                setLabel('Choose location');
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

    function detect() {
        if (!detectUrl || busy) {
            return;
        }

        busy = true;
        setStatus('Detecting…');
        setLabel('Detecting location…');

        // LAN / HTTP hosts (e.g. 192.168.x.x) cannot use browser GPS — use IP immediately.
        var canUseGps = typeof navigator !== 'undefined'
            && navigator.geolocation
            && (window.isSecureContext || location.hostname === 'localhost' || location.hostname === '127.0.0.1');

        if (!canUseGps) {
            detectByIp('Using network location');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            function (position) {
                postDetect({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                })
                    .then(function (result) {
                        if (result.ok && result.data && result.data.ok) {
                            applyResult(result);
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
                detectByIp('Could not detect location');
            },
            {
                enableHighAccuracy: false,
                timeout: 8000,
                maximumAge: 300000,
            }
        );
    }

    if (detectBtn) {
        detectBtn.addEventListener('click', function () {
            detect();
        });
    }

    if (autoDetect) {
        detect();
    }
})();
