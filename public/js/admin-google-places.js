/**
 * Google Places autocomplete for admin address fields.
 * Suggestions only while the address field is focused; hidden on blur.
 */
(function () {
    function component(components, type) {
        const match = (components || []).find((item) => (item.types || []).includes(type));
        return match ? match.long_name : '';
    }

    function setInputValue(id, value) {
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
            el.classList.add('jb-places-hidden');
            el.style.display = 'none';
        });
    }

    function showSuggestions() {
        pacContainers().forEach((el) => {
            el.classList.remove('jb-places-hidden');
            el.style.display = '';
        });
    }

    function applyLocationPicker(picker, place) {
        if (! picker || ! window.Alpine || typeof Alpine.$data !== 'function') {
            return;
        }

        const data = Alpine.$data(picker);
        if (! data) {
            return;
        }

        const components = place.address_components || [];
        const country = component(components, 'country');
        const state = component(components, 'administrative_area_level_1');
        const city = component(components, 'locality')
            || component(components, 'administrative_area_level_2')
            || component(components, 'sublocality_level_1')
            || component(components, 'postal_town');

        data.countryId = data.otherValue || 'other';
        data.countryOther = country;
        data.stateId = data.otherValue || 'other';
        data.stateOther = state;
        data.cityId = data.otherValue || 'other';
        data.cityOther = city;
    }

    function bindAddressInput(input) {
        if (! input || input.dataset.jbPlacesBound === '1' || ! window.google?.maps?.places) {
            return;
        }

        input.dataset.jbPlacesBound = '1';

        const wrap = input.closest('.jb-places-wrap') || input.parentElement;
        const autocomplete = new google.maps.places.Autocomplete(input, {
            fields: ['address_components', 'formatted_address', 'geometry', 'name'],
        });

        // Keep dropdown visually tied to the address field.
        if (wrap) {
            wrap.classList.add('jb-places-wrap--ready');
        }

        let blurTimer = null;

        input.addEventListener('focus', function () {
            if (blurTimer) {
                clearTimeout(blurTimer);
                blurTimer = null;
            }
            showSuggestions();
        });

        input.addEventListener('input', function () {
            showSuggestions();
        });

        input.addEventListener('keydown', function () {
            showSuggestions();
        });

        input.addEventListener('blur', function () {
            // Delay so a click on a suggestion still registers.
            blurTimer = setTimeout(hideSuggestions, 180);
        });

        // If user clicks a suggestion, keep it visible briefly then hide after selection.
        document.addEventListener('mousedown', function (event) {
            const pac = event.target.closest('.pac-container');
            if (pac) {
                if (blurTimer) {
                    clearTimeout(blurTimer);
                    blurTimer = null;
                }
            }
        }, true);

        autocomplete.addListener('place_changed', function () {
            const place = autocomplete.getPlace();
            hideSuggestions();

            if (! place || ! place.geometry || ! place.geometry.location) {
                return;
            }

            const lat = place.geometry.location.lat();
            const lng = place.geometry.location.lng();
            const prefix = (input.name || 'address').replace(/address$/, '');

            setInputValue(prefix + 'latitude', Number(lat).toFixed(7));
            setInputValue(prefix + 'longitude', Number(lng).toFixed(7));

            if (place.formatted_address) {
                input.value = place.formatted_address;
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }

            const form = input.closest('form');
            const picker = form?.querySelector('[data-jb-places-location]')
                || document.querySelector('[data-jb-places-location]');
            applyLocationPicker(picker, place);

            const pincode = component(place.address_components || [], 'postal_code');
            if (pincode) {
                setInputValue(prefix + 'pincode', pincode);
            }

            input.blur();
        });
    }

    window.initAdminGooglePlaces = function () {
        document.querySelectorAll('[data-jb-places-address]').forEach(bindAddressInput);
        hideSuggestions();
    };

    if (window.google?.maps?.places) {
        window.initAdminGooglePlaces();
    }
})();
