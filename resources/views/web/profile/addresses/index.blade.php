@extends('web.layouts.profile')

@section('title', 'Saved Addresses')

@php
    $mapsEnabled = filled(config('services.google.maps_api_key'));
@endphp

@section('content')
<div class="jbw-card jbw-profile-panel">
    <div class="jbw-profile-panel-head">
        <h2 class="jbw-profile-panel-title">Saved Addresses</h2>
        <p class="jbw-profile-panel-sub">View and manage your delivery addresses.</p>
    </div>

    <div class="jbw-address-grid">
        @forelse ($addresses as $address)
            <div class="jbw-bh-card jbw-address-card">
                <div style="display:flex;justify-content:space-between;gap:0.75rem;align-items:start">
                    <span class="jbw-address-tag">{{ $address->label }}</span>
                    @if ($address->is_default)
                        <span style="font-size:0.6875rem;font-weight:800;color:var(--c-primary)">DEFAULT</span>
                    @endif
                </div>
                <p style="margin:0.5rem 0 0;line-height:1.6;color:var(--c-muted)">
                    {{ $address->name }}<br>
                    {{ $address->fullAddress() }}
                    @if ($address->mobile_number)<br>{{ $address->mobile_number }}@endif
                </p>
                <form method="POST" action="{{ route('web.profile.addresses.destroy', $address) }}" style="margin-top:1rem" onsubmit="return confirm('Remove this address?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="jbw-btn jbw-btn--ghost jbw-btn--sm" style="color:#dc2626">Remove</button>
                </form>
            </div>
        @empty
            <div class="jbw-bh-card jbw-address-card">
                <span class="jbw-address-tag">PROFILE</span>
                <p style="margin:0.5rem 0 0;line-height:1.6;color:var(--c-muted)">
                    {{ $customer->name }}<br>
                    {{ $customer->city ?? 'Add your city in profile' }}<br>
                    India
                </p>
            </div>
        @endforelse
    </div>

    <div class="jbw-bh-card" style="margin-top:1.25rem">
        <p class="jbw-profile-panel-title" style="font-size:1rem;margin-bottom:1rem">Add new address</p>
        <form method="POST" action="{{ route('web.profile.addresses.store') }}" class="jbw-form-stack" data-jbw-address-form>
            @csrf

            @if ($mapsEnabled)
                <div class="jbw-address-map-block">
                    <div class="jbw-field jbw-address-places-wrap">
                        <label class="jbw-label" for="place_search">Search location</label>
                        <input
                            id="place_search"
                            type="text"
                            class="jbw-input jbw-address-places-input"
                            placeholder="Start typing to search address…"
                            autocomplete="off"
                        >
                        <p class="jbw-profile-edit-hint">Pick a suggestion to fill the fields below.</p>
                    </div>

                    <div class="jbw-address-map-actions">
                        <button type="button" class="jbw-btn jbw-btn--outline jbw-btn--sm" data-use-current-location>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                <path d="M12 21s7-4.5 7-10a7 7 0 10-14 0c0 5.5 7 10 7 10z"/>
                                <circle cx="12" cy="11" r="2.5"/>
                            </svg>
                            Use my current location
                        </button>
                        <p class="jbw-address-map-status" data-address-map-status>Or tap the map / drag the pin to set location.</p>
                    </div>

                    <div id="jbw-address-map" class="jbw-address-map" role="application" aria-label="Address map"></div>
                    <input type="hidden" id="latitude" name="latitude" value="{{ old('latitude') }}">
                    <input type="hidden" id="longitude" name="longitude" value="{{ old('longitude') }}">
                    <input type="hidden" id="country" name="country" value="{{ old('country', 'India') }}">
                </div>
            @else
                <p class="jbw-profile-edit-hint" style="margin-bottom:1rem">Map search is unavailable until a Google Maps API key is configured.</p>
            @endif

            <div class="jbw-measure-form-grid" style="grid-template-columns:repeat(2,1fr)">
                <div class="jbw-field">
                    <label class="jbw-label" for="label">Label</label>
                    <select id="label" name="label" class="jbw-select" required>
                        <option value="HOME" @selected(old('label', 'HOME') === 'HOME')>Home</option>
                        <option value="WORK" @selected(old('label') === 'WORK')>Work</option>
                        <option value="OTHER" @selected(old('label') === 'OTHER')>Other</option>
                    </select>
                </div>
                <div class="jbw-field">
                    <label class="jbw-label" for="name">Recipient name</label>
                    <input id="name" type="text" name="name" class="jbw-input" value="{{ old('name', $customer->name) }}">
                </div>
                <div class="jbw-field">
                    <label class="jbw-label" for="house_no">House / flat no.</label>
                    <input id="house_no" type="text" name="house_no" class="jbw-input" value="{{ old('house_no') }}" required>
                    @error('house_no')<p class="jbw-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="jbw-field">
                    <label class="jbw-label" for="road_area">Street / area</label>
                    <input id="road_area" type="text" name="road_area" class="jbw-input" value="{{ old('road_area') }}" required>
                    @error('road_area')<p class="jbw-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="jbw-field">
                    <label class="jbw-label" for="city">City</label>
                    <input id="city" type="text" name="city" class="jbw-input" value="{{ old('city', $customer->city) }}" required>
                    @error('city')<p class="jbw-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="jbw-field">
                    <label class="jbw-label" for="state">State</label>
                    <input id="state" type="text" name="state" class="jbw-input" value="{{ old('state') }}">
                </div>
                <div class="jbw-field">
                    <label class="jbw-label" for="pincode">Pincode</label>
                    <input id="pincode" type="text" name="pincode" class="jbw-input" value="{{ old('pincode') }}" maxlength="10" required>
                    @error('pincode')<p class="jbw-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="jbw-field">
                    <label class="jbw-label" for="mobile_number">Mobile</label>
                    <input id="mobile_number" type="text" name="mobile_number" class="jbw-input" value="{{ old('mobile_number', $customer->mobile) }}">
                </div>
            </div>
            <label style="display:flex;align-items:center;gap:0.5rem;margin-top:0.5rem;font-size:0.875rem">
                <input type="checkbox" name="is_default" value="1" @checked(old('is_default'))>
                Set as default address
            </label>
            <button type="submit" class="jbw-btn jbw-btn--primary" style="margin-top:1rem">Save address</button>
        </form>
    </div>
</div>
@endsection

@if ($mapsEnabled)
@push('scripts')
<style>
.jbw-address-map-block {
    display: grid;
    gap: 0.85rem;
    margin-bottom: 1.25rem;
    padding-bottom: 1.25rem;
    border-bottom: 1px solid var(--c-border);
}
.jbw-address-places-wrap { position: relative; z-index: 5; }
.jbw-address-map-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem 1rem;
}
.jbw-address-map-actions .jbw-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}
.jbw-address-map-status {
    margin: 0;
    font-size: 0.8125rem;
    color: var(--c-muted);
    flex: 1;
    min-width: 12rem;
}
.jbw-address-map-status.is-error { color: #dc2626; }
.jbw-address-map {
    width: 100%;
    height: 240px;
    border-radius: 12px;
    border: 1px solid var(--c-border);
    overflow: hidden;
    background: #eef2f6;
}
.pac-container {
    z-index: 10000 !important;
    border-radius: 10px;
    border: 1px solid var(--c-border);
    box-shadow: var(--c-shadow-md);
    margin-top: 4px;
    font-family: inherit;
}
@media (max-width: 640px) {
    .jbw-address-map { height: 200px; }
}
</style>
<script src="{{ asset('js/web-address-places.js') }}?v={{ @filemtime(public_path('js/web-address-places.js')) }}"></script>
<script
    async
    defer
    src="https://maps.googleapis.com/maps/api/js?key={{ urlencode(config('services.google.maps_api_key')) }}&libraries=places&callback=initWebAddressPlaces"
></script>
@endpush
@endif
