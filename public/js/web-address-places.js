/**
 * Google Places + map picker for customer saved-address form.
 * Expects form [data-jbw-address-form] with:
 * #place_search, #house_no, #road_area, #city, #state, #pincode, #country (optional),
 * #latitude, #longitude, #jbw-address-map, [data-use-current-location]
 */
(function () {
    var DEFAULT_CENTER = { lat: 22.7196, lng: 75.8577 }; // Indore
    var map = null;
    var marker = null;
    var geocoder = null;
    var statusEl = null;

    function $(sel, root) {
        return (root || document).querySelector(sel);
    }

    function component(components, type) {
        var match = (components || []).find(function (item) {
            return (item.types || []).includes(type);
        });
        return match ? match.long_name : '';
    }

    function setValue(id, value) {
        var el = document.getElementById(id);
        if (!el) return;
        el.value = value == null ? '' : String(value);
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function setStatus(message, isError) {
        if (!statusEl) return;
        statusEl.textContent = message || '';
        statusEl.classList.toggle('is-error', !!isError);
    }

    function fillFromComponents(components) {
        var streetNumber = component(components, 'street_number');
        var premise = component(components, 'premise') || component(components, 'subpremise');
        var route = component(components, 'route');
        var sublocality = component(components, 'sublocality_level_1')
            || component(components, 'sublocality')
            || component(components, 'neighborhood');
        var city = component(components, 'locality')
            || component(components, 'administrative_area_level_2')
            || component(components, 'postal_town');
        var state = component(components, 'administrative_area_level_1');
        var country = component(components, 'country');
        var pincode = component(components, 'postal_code');

        var house = streetNumber || premise;
        if (house) {
            setValue('house_no', house);
        }

        var roadParts = [route, sublocality].filter(Boolean);
        if (roadParts.length) {
            setValue('road_area', roadParts.join(', '));
        } else if (!document.getElementById('road_area').value && premise && streetNumber) {
            setValue('road_area', premise);
        }

        if (city) setValue('city', city);
        if (state) setValue('state', state);
        if (country) setValue('country', country);
        if (pincode) setValue('pincode', pincode);
    }

    function setCoords(lat, lng) {
        setValue('latitude', Number(lat).toFixed(7));
        setValue('longitude', Number(lng).toFixed(7));
    }

    function moveMarker(lat, lng, pan) {
        if (!map || !marker) return;
        var pos = { lat: Number(lat), lng: Number(lng) };
        marker.setPosition(pos);
        if (pan !== false) {
            map.panTo(pos);
        }
    }

    function reverseGeocode(lat, lng, opts) {
        opts = opts || {};
        if (!geocoder) return;

        setStatus(opts.pendingMessage || 'Fetching address…');
        geocoder.geocode({ location: { lat: Number(lat), lng: Number(lng) } }, function (results, status) {
            if (status !== 'OK' || !results || !results.length) {
                setStatus('Could not fetch address for this location.', true);
                return;
            }

            fillFromComponents(results[0].address_components || []);
            setCoords(lat, lng);
            moveMarker(lat, lng, opts.pan !== false);

            var search = document.getElementById('place_search');
            if (search && results[0].formatted_address) {
                search.value = results[0].formatted_address;
            }

            setStatus('Address filled from map. Adjust fields if needed.');
        });
    }

    function useCurrentLocation() {
        if (!navigator.geolocation) {
            setStatus('Location is not supported in this browser.', true);
            return;
        }

        setStatus('Detecting your current location…');
        navigator.geolocation.getCurrentPosition(
            function (position) {
                var lat = position.coords.latitude;
                var lng = position.coords.longitude;
                setCoords(lat, lng);
                moveMarker(lat, lng, true);
                if (map) {
                    map.setZoom(16);
                }
                reverseGeocode(lat, lng, { pendingMessage: 'Fetching address for your location…' });
            },
            function () {
                setStatus('Could not access your location. Allow location permission and try again.', true);
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    }

    function initAutocomplete(form) {
        var input = document.getElementById('place_search');
        if (!input || !window.google?.maps?.places) return;

        var autocomplete = new google.maps.places.Autocomplete(input, {
            fields: ['address_components', 'formatted_address', 'geometry', 'name'],
            componentRestrictions: { country: ['in'] },
        });

        if (form) {
            form.addEventListener('submit', function (e) {
                if (document.activeElement === input) {
                    e.preventDefault();
                }
            });
        }

        autocomplete.addListener('place_changed', function () {
            var place = autocomplete.getPlace();
            fillFromComponents(place?.address_components || []);

            if (place?.geometry?.location) {
                var lat = place.geometry.location.lat();
                var lng = place.geometry.location.lng();
                setCoords(lat, lng);
                moveMarker(lat, lng, true);
                if (map) map.setZoom(16);
            }

            if (place?.formatted_address) {
                input.value = place.formatted_address;
            }

            setStatus('Address filled from search. Adjust fields if needed.');
        });
    }

    function initMap(form) {
        var mapEl = document.getElementById('jbw-address-map');
        if (!mapEl || !window.google?.maps) return;

        var latInput = document.getElementById('latitude');
        var lngInput = document.getElementById('longitude');
        var startLat = parseFloat(latInput?.value);
        var startLng = parseFloat(lngInput?.value);
        var center = (Number.isFinite(startLat) && Number.isFinite(startLng))
            ? { lat: startLat, lng: startLng }
            : DEFAULT_CENTER;

        map = new google.maps.Map(mapEl, {
            center: center,
            zoom: (Number.isFinite(startLat) && Number.isFinite(startLng)) ? 16 : 12,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false,
        });

        marker = new google.maps.Marker({
            map: map,
            position: center,
            draggable: true,
            title: 'Delivery location',
        });

        geocoder = new google.maps.Geocoder();

        map.addListener('click', function (event) {
            if (!event.latLng) return;
            reverseGeocode(event.latLng.lat(), event.latLng.lng());
        });

        marker.addListener('dragend', function (event) {
            if (!event.latLng) return;
            reverseGeocode(event.latLng.lat(), event.latLng.lng(), { pan: false });
        });
    }

    window.initWebAddressPlaces = function () {
        var form = $('[data-jbw-address-form]');
        if (!form || !window.google?.maps) return;

        statusEl = form.querySelector('[data-address-map-status]');
        initMap(form);
        initAutocomplete(form);

        var locateBtn = form.querySelector('[data-use-current-location]');
        locateBtn?.addEventListener('click', function (e) {
            e.preventDefault();
            useCurrentLocation();
        });
    };

    if (window.google?.maps) {
        window.initWebAddressPlaces();
    }
})();
