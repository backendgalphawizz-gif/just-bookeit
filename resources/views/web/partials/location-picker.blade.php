@php
    $currentCityId = $webLocationCurrent['city_id'] ?? null;
    $currentAddressId = $webLocationCurrent['address_id'] ?? null;
    $popularNames = ['Mumbai', 'Delhi', 'Bengaluru', 'Bangalore', 'Hyderabad', 'Chennai', 'Pune', 'Kolkata', 'Ahmedabad', 'Jaipur'];
    $popularCities = collect($webLocationCities)->filter(
        fn (array $city) => in_array($city['name'], $popularNames, true)
    )->take(8)->values();
@endphp

<div class="jbw-location-picker ">
    <button
        type="button"
        class="jbw-location-btn headerinputradius headerinputradiuses bordercolor"
        @click="locationOpen = !locationOpen; notificationOpen = false"
        :aria-expanded="locationOpen"
        aria-haspopup="listbox"
    >
        <svg
    width="18"
    height="18"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2.5"
    style="color: rgb(242 81 35);"
    aria-hidden="true">
    <path d="M12 21s7-4.5 7-10a7 7 0 10-14 0c0 5.5 7 10 7 10z"/>
    <circle cx="12" cy="11" r="2.5"/>
</svg>
        <span class="jbw-location-btn-label">{{ $webLocationLabel }}</span>
    </button>

    <div
        class="jbw-location-panel"
        x-show="locationOpen"
        x-cloak
        @click.outside="locationOpen = false"
        @keydown.escape.window="locationOpen = false"
        role="dialog"
        aria-label="Choose location"
    >
        <div class="jbw-location-panel-head">
            <p class="jbw-location-panel-title">Choose location</p>
            <button type="button" class="jbw-location-panel-close" @click="locationOpen = false" aria-label="Close">&times;</button>
        </div>

        <div class="jbw-location-search-wrap">
            <input
                type="search"
                x-model="locationSearch"
                class="jbw-input jbw-location-search"
                placeholder="Search city or state…"
                aria-label="Search locations"
            >
        </div>

        @if ($webLocationAddresses->isNotEmpty())
            <div class="jbw-location-section">
                <p class="jbw-location-section-title">Saved addresses</p>
                <div class="jbw-location-options">
                    @foreach ($webLocationAddresses as $address)
                        <form method="POST" action="{{ route('web.location.update') }}" class="jbw-location-option-form">
                            @csrf
                            <input type="hidden" name="address_id" value="{{ $address->id }}">
                            <button
                                type="submit"
                                @class(['jbw-location-option', 'is-active' => $currentAddressId === $address->id])
                                x-show="!locationSearch || {{ Illuminate\Support\Js::from(mb_strtolower($address->fullAddress())) }}.includes(locationSearch.toLowerCase())"
                            >
                                <span class="jbw-location-option-label">{{ $address->label }}</span>
                                <span class="jbw-location-option-meta">{{ \App\Support\WebLocation::addressLabel($address) }}</span>
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($popularCities->isNotEmpty())
            <div class="jbw-location-section">
                <p class="jbw-location-section-title">Popular cities</p>
                <div class="jbw-location-chips">
                    @foreach ($popularCities as $city)
                        <form method="POST" action="{{ route('web.location.update') }}">
                            @csrf
                            <input type="hidden" name="city_id" value="{{ $city['id'] }}">
                            <button
                                type="submit"
                                @class(['jbw-location-chip', 'is-active' => $currentCityId === $city['id']])
                                x-show="!locationSearch || {{ Illuminate\Support\Js::from($city['search']) }}.includes(locationSearch.toLowerCase())"
                            >{{ $city['name'] }}</button>
                        </form>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="jbw-location-section jbw-location-section--scroll">
            <p class="jbw-location-section-title">All cities</p>
            <div class="jbw-location-options">
                @foreach ($webLocationCities as $city)
                    <form method="POST" action="{{ route('web.location.update') }}" class="jbw-location-option-form">
                        @csrf
                        <input type="hidden" name="city_id" value="{{ $city['id'] }}">
                        <button
                            type="submit"
                            @class(['jbw-location-option', 'is-active' => $currentCityId === $city['id'] && ! $currentAddressId])
                            x-show="!locationSearch || {{ Illuminate\Support\Js::from($city['search']) }}.includes(locationSearch.toLowerCase())"
                        >
                            <span class="jbw-location-option-label">{{ $city['name'] }}</span>
                            <span class="jbw-location-option-meta">{{ $city['state'] }}, {{ $city['country'] }}</span>
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    </div>
</div>
