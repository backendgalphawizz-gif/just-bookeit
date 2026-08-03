/**
 * Google Places autocomplete + map pin picker for vendor location fields.
 * Expects: #address, #city, #state, #country, #pincode, #latitude, #longitude, #vp-location-map
 */
(function () {
    const DEFAULT_CENTER = { lat: 22.7196, lng: 75.8577 }; // Indore
    const DEFAULT_ZOOM = 13;

    function component(components, type) {
        const match = (components || []).find((item) => (item.types || []).includes(type));
        return match ? match.long_name : '';
    }

    function setValue(id, value) {
        const el = document.getElementById(id);
        if (! el) {
            return;
        }
        el.value = value ?? '';
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function readLatLng() {
        const lat = parseFloat(document.getElementById('latitude')?.value || '');
        const lng = parseFloat(document.getElementById('longitude')?.value || '');
        if (Number.isFinite(lat) && Number.isFinite(lng)) {
            return { lat, lng };
        }

        return null;
    }

    function pacContainers() {
        return Array.from(document.querySelectorAll('.pac-container'));
    }

    function hideSuggestions() {
        pacContainers().forEach((el) => {
            el.classList.add('vp-places-hidden');
            el.style.display = 'none';
        });
    }

    function showSuggestions() {
        pacContainers().forEach((el) => {
            el.classList.remove('vp-places-hidden');
            el.style.display = '';
        });
    }

    function fillFromComponents(components, formattedAddress) {
        if (formattedAddress) {
            setValue('address', formattedAddress);
        }

        const city = component(components, 'locality')
            || component(components, 'administrative_area_level_2')
            || component(components, 'sublocality_level_1')
            || component(components, 'postal_town');
        const state = component(components, 'administrative_area_level_1');
        const country = component(components, 'country');
        const pincode = component(components, 'postal_code');

        if (city) {
            setValue('city', city);
        }
        if (state) {
            setValue('state', state);
        }
        if (country) {
            setValue('country', country);
        }
        if (pincode) {
            setValue('pincode', pincode);
        }
    }

    function setCoords(lat, lng) {
        setValue('latitude', Number(lat).toFixed(7));
        setValue('longitude', Number(lng).toFixed(7));
    }

    window.initVendorGooglePlaces = function () {
        if (! window.google?.maps?.places) {
            return;
        }

        const addressInput = document.getElementById('address');
        const mapEl = document.getElementById('vp-location-map');
        if (! addressInput || ! mapEl) {
            return;
        }

        const initial = readLatLng() || DEFAULT_CENTER;
        const map = new google.maps.Map(mapEl, {
            center: initial,
            zoom: DEFAULT_ZOOM,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false,
            gestureHandling: 'greedy',
        });

        const marker = new google.maps.Marker({
            map,
            position: initial,
            draggable: true,
            title: 'Drag to set shop location',
        });

        const geocoder = new google.maps.Geocoder();

        function moveMarker(latLng, reverseGeocode) {
            marker.setPosition(latLng);
            map.panTo(latLng);
            setCoords(latLng.lat(), latLng.lng());

            if (! reverseGeocode) {
                return;
            }

            geocoder.geocode({ location: latLng }, function (results, status) {
                if (status !== 'OK' || ! results?.[0]) {
                    return;
                }
                fillFromComponents(results[0].address_components || [], results[0].formatted_address || '');
            });
        }

        marker.addListener('dragend', function () {
            const position = marker.getPosition();
            if (position) {
                moveMarker(position, true);
            }
        });

        map.addListener('click', function (event) {
            if (event.latLng) {
                moveMarker(event.latLng, true);
            }
        });

        const autocomplete = new google.maps.places.Autocomplete(addressInput, {
            fields: ['address_components', 'formatted_address', 'geometry', 'name'],
            componentRestrictions: { country: ['in'] },
        });
        autocomplete.bindTo('bounds', map);

        let blurTimer = null;
        addressInput.addEventListener('focus', function () {
            if (blurTimer) {
                clearTimeout(blurTimer);
                blurTimer = null;
            }
            showSuggestions();
        });
        addressInput.addEventListener('input', showSuggestions);
        addressInput.addEventListener('keydown', showSuggestions);
        addressInput.addEventListener('blur', function () {
            blurTimer = setTimeout(hideSuggestions, 180);
        });
        document.addEventListener('mousedown', function (event) {
            if (event.target.closest('.pac-container') && blurTimer) {
                clearTimeout(blurTimer);
                blurTimer = null;
            }
        }, true);

        autocomplete.addListener('place_changed', function () {
            const place = autocomplete.getPlace();
            hideSuggestions();

            if (! place?.geometry?.location) {
                return;
            }

            const location = place.geometry.location;
            fillFromComponents(place.address_components || [], place.formatted_address || addressInput.value);
            setCoords(location.lat(), location.lng());

            if (place.geometry.viewport) {
                map.fitBounds(place.geometry.viewport);
            } else {
                map.setCenter(location);
                map.setZoom(16);
            }
            marker.setPosition(location);
            addressInput.blur();
        });

        // Seed coords if empty so form still posts a pin after user only types later.
        if (! readLatLng()) {
            setCoords(initial.lat, initial.lng);
        }

        hideSuggestions();
        mapEl.classList.add('is-ready');
    };

    if (window.google?.maps?.places) {
        window.initVendorGooglePlaces();
    }
})();
