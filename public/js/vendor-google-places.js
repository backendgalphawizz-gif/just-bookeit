/**
 * Google Places autocomplete for vendor location fields.
 * Expects: #address, #city, #state, #country, #pincode, optional #latitude/#longitude
 */
(function () {
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
        if (! addressInput || addressInput.tagName === 'TEXTAREA') {
            return;
        }

        const autocomplete = new google.maps.places.Autocomplete(addressInput, {
            fields: ['address_components', 'formatted_address', 'geometry', 'name'],
            componentRestrictions: { country: ['in'] },
        });

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

            fillFromComponents(
                place?.address_components || [],
                place?.formatted_address || addressInput.value
            );

            if (place?.geometry?.location) {
                setCoords(place.geometry.location.lat(), place.geometry.location.lng());
            }

            addressInput.blur();
        });

        hideSuggestions();
    };

    if (window.google?.maps?.places) {
        window.initVendorGooglePlaces();
    }
})();
