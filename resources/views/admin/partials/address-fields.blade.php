@php
    use App\Models\Country;
    use App\Support\LocationResolver;

    $prefix = $prefix ?? '';
    $values = $values ?? [];
    $value = fn (string $field) => old($prefix.$field, $values[$field] ?? '');
    $field = fn (string $name) => $prefix.$name;
    $savedDefaults = LocationResolver::formDefaultsFromNames($value('country'), $value('state'), $value('city'));
    $locationCatalog = LocationResolver::catalog();
    $countries = Country::query()->where('is_active', true)->orderBy('name')->get();

    $countryId = old($field('country_id'), $savedDefaults['country_id']);
    $stateId = old($field('state_id'), $savedDefaults['state_id']);
    $cityId = old($field('city_id'), $savedDefaults['city_id']);
    $countryOther = old($field('country_other'), $savedDefaults['country_other']);
    $stateOther = old($field('state_other'), $savedDefaults['state_other']);
    $cityOther = old($field('city_other'), $savedDefaults['city_other']);
@endphp

<p class="jb-form-section-title sm:col-span-2">Address</p>
@php
    $enableGooglePlaces = (bool) ($enableGooglePlaces ?? false)
        && filled(config('services.google.maps_api_key'));
    $addressInputId = $field('address');
@endphp
@if ($enableGooglePlaces)
    <div class="sm:col-span-2 jb-places-wrap">
        <label for="{{ $addressInputId }}" class="jb-label">Address</label>
        <input
            type="text"
            id="{{ $addressInputId }}"
            name="{{ $prefix }}address"
            value="{{ $value('address') }}"
            class="jb-input jb-places-input"
            placeholder="Start typing to search address…"
            autocomplete="off"
            data-jb-places-address
            data-jb-restrict="text"
            maxlength="500"
            data-jb-max-chars="500"
        >
        <p class="mt-1 text-xs text-slate-500">Focus this field to see address suggestions. Click away to hide them.</p>
        @error($prefix.'address')
            <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
        @enderror
        <input type="hidden" name="{{ $prefix }}latitude" id="{{ $field('latitude') }}" value="{{ $value('latitude') }}">
        <input type="hidden" name="{{ $prefix }}longitude" id="{{ $field('longitude') }}" value="{{ $value('longitude') }}">
    </div>
@else
    @include('admin.partials.form-input', [
        'label' => 'Address',
        'name' => $prefix.'address',
        'value' => $value('address'),
        'full' => true,
    ])
@endif

<div
    class="jb-location-picker sm:col-span-2{{ $enableGooglePlaces ? ' jb-location-picker--after-places' : '' }}"
    @if ($enableGooglePlaces) data-jb-places-location @endif
    x-data="{
        catalog: @js($locationCatalog),
        countryId: @js((string) $countryId),
        stateId: @js((string) $stateId),
        cityId: @js((string) $cityId),
        countryOther: @js($countryOther),
        stateOther: @js($stateOther),
        cityOther: @js($cityOther),
        otherValue: 'other',
        usesCustomCity() {
            return this.countryId === this.otherValue || this.stateId === this.otherValue;
        },
        states() {
            const country = this.catalog.find((item) => String(item.id) === String(this.countryId));
            return country ? country.states : [];
        },
        cities() {
            const state = this.states().find((item) => String(item.id) === String(this.stateId));
            return state ? state.cities : [];
        },
        fillSelect(select, items, placeholder, selectedValue) {
            if (! select) {
                return;
            }

            select.innerHTML = '';
            const blank = document.createElement('option');
            blank.value = '';
            blank.textContent = placeholder;
            select.appendChild(blank);

            items.forEach((item) => {
                const option = document.createElement('option');
                option.value = String(item.id);
                option.textContent = item.name;
                select.appendChild(option);
            });

            const other = document.createElement('option');
            other.value = this.otherValue;
            other.textContent = 'Other (add new)';
            select.appendChild(other);

            select.value = selectedValue ? String(selectedValue) : '';
        },
        refreshStateOptions(keepSelection = true) {
            const selected = keepSelection ? this.stateId : '';
            this.fillSelect(this.$refs.stateSelect, this.states(), 'Select state', selected);

            if (! keepSelection) {
                this.stateId = '';
            }
        },
        refreshCityOptions(keepSelection = true) {
            const selected = keepSelection ? this.cityId : '';
            this.fillSelect(this.$refs.citySelect, this.cities(), 'Select city', selected);

            if (! keepSelection) {
                this.cityId = '';
            }
        },
        onCountryChange() {
            this.stateId = '';
            this.cityId = '';
            this.stateOther = '';
            this.cityOther = '';
            this.refreshStateOptions(false);
            this.refreshCityOptions(false);
        },
        onStateChange() {
            if (this.stateId === this.otherValue) {
                this.cityId = this.otherValue;
            } else {
                this.cityId = '';
            }
            this.cityOther = '';
            this.refreshCityOptions(false);
        },
        init() {
            this.$nextTick(() => {
                this.refreshStateOptions(true);
                this.refreshCityOptions(true);
            });
        },
    }"
    x-init="init()"
>
    <div class="jb-form-grid">
        <div>
            <label for="{{ $field('country_id') }}" class="jb-label">Country</label>
            <select
                id="{{ $field('country_id') }}"
                name="{{ $field('country_id') }}"
                class="jb-select"
                x-model="countryId"
                @change="onCountryChange()"
            >
                <option value="">Select country</option>
                @foreach ($countries as $country)
                    <option value="{{ $country->id }}" @selected((string) $countryId === (string) $country->id)>
                        {{ $country->name }}
                    </option>
                @endforeach
                <option value="other" @selected($countryId === 'other')>Other (add new)</option>
            </select>
            <div class="jb-location-other" x-show="countryId === otherValue" x-cloak>
                <input
                    type="text"
                    name="{{ $field('country_other') }}"
                    class="jb-input mt-2"
                    placeholder="Enter country name"
                    x-model="countryOther"
                    value="{{ $countryOther }}"
                    maxlength="100"
                >
            </div>
            @error($field('country_id'))<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            @error($field('country_other'))<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="{{ $field('state_id') }}" class="jb-label">State</label>
            <template x-if="countryId !== otherValue">
                <div>
                    <select
                        x-ref="stateSelect"
                        id="{{ $field('state_id') }}"
                        name="{{ $field('state_id') }}"
                        class="jb-select"
                        x-model="stateId"
                        @change="onStateChange()"
                        :disabled="!countryId"
                    ></select>
                    <div class="jb-location-other" x-show="stateId === otherValue" x-cloak>
                        <input
                            type="text"
                            name="{{ $field('state_other') }}"
                            class="jb-input mt-2"
                            placeholder="Enter state name"
                            x-model="stateOther"
                            value="{{ $stateOther }}"
                            maxlength="100"
                        >
                    </div>
                </div>
            </template>
            <template x-if="countryId === otherValue">
                <div>
                    <input type="hidden" name="{{ $field('state_id') }}" value="other">
                    <input
                        type="text"
                        id="{{ $field('state_id') }}"
                        name="{{ $field('state_other') }}"
                        class="jb-input"
                        placeholder="Enter state name"
                        x-model="stateOther"
                        value="{{ $stateOther }}"
                        maxlength="100"
                    >
                </div>
            </template>
            @error($field('state_id'))<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            @error($field('state_other'))<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="{{ $field('city_other') }}" class="jb-label">City</label>
            <template x-if="!usesCustomCity()">
                <div>
                    <select
                        x-ref="citySelect"
                        id="{{ $field('city_id') }}"
                        name="{{ $field('city_id') }}"
                        class="jb-select"
                        x-model="cityId"
                        :disabled="!stateId || stateId === otherValue"
                    ></select>
                    <div class="jb-location-other" x-show="cityId === otherValue" x-cloak>
                        <input
                            type="text"
                            id="{{ $field('city_other') }}"
                            name="{{ $field('city_other') }}"
                            class="jb-input mt-2"
                            placeholder="Enter city name"
                            x-model="cityOther"
                            value="{{ $cityOther }}"
                            maxlength="100"
                        >
                    </div>
                </div>
            </template>
            <template x-if="usesCustomCity()">
                <div>
                    <input type="hidden" name="{{ $field('city_id') }}" value="other">
                    <input
                        type="text"
                        id="{{ $field('city_other') }}"
                        name="{{ $field('city_other') }}"
                        class="jb-input"
                        placeholder="Enter city name"
                        x-model="cityOther"
                        value="{{ $cityOther }}"
                        maxlength="100"
                    >
                </div>
            </template>
            @error($field('city_id'))<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            @error($field('city_other'))<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            @include('admin.partials.form-input', ['label' => 'Pincode', 'name' => $prefix.'pincode', 'value' => $value('pincode')])
        </div>
    </div>
</div>

@if ($enableGooglePlaces)
    @once
        @push('styles')
            <style>
                .jb-places-wrap {
                    position: relative;
                    z-index: 30;
                    margin-bottom: 0.25rem;
                }

                .jb-places-input {
                    margin-bottom: 0 !important;
                }

                .jb-location-picker--after-places {
                    position: relative;
                    z-index: 1;
                    margin-top: 0.75rem;
                }

                /* Google Places dropdown */
                .pac-container {
                    z-index: 99999 !important;
                    margin-top: 6px;
                    border: 1px solid #e2e8f0;
                    border-radius: 12px;
                    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.14);
                    font-family: "Plus Jakarta Sans", system-ui, sans-serif;
                    overflow: hidden;
                    background: #fff;
                }

                .pac-container.jb-places-hidden {
                    display: none !important;
                    visibility: hidden !important;
                    pointer-events: none !important;
                    opacity: 0 !important;
                }

                .pac-item {
                    padding: 10px 12px;
                    border-top: 1px solid #f1f5f9;
                    line-height: 1.35;
                    cursor: pointer;
                }

                .pac-item:first-child {
                    border-top: 0;
                }

                .pac-item:hover,
                .pac-item-selected {
                    background: #fff1f2;
                }

                .pac-item-query {
                    font-weight: 600;
                    color: #0f172a;
                }

                .pac-icon {
                    margin-top: 4px;
                }
            </style>
        @endpush
        @push('scripts')
            <script src="{{ asset('js/admin-google-places.js') }}?v={{ @filemtime(public_path('js/admin-google-places.js')) }}"></script>
            <script
                src="https://maps.googleapis.com/maps/api/js?key={{ urlencode(config('services.google.maps_api_key')) }}&libraries=places&callback=initAdminGooglePlaces"
                async
                defer
            ></script>
        @endpush
    @endonce
@endif
