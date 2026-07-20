@extends('web.layouts.profile')

@section('title', 'Addresses')
@section('page_title', 'Address')
@section('page_subtitle', 'View and manage your delivery addresses.')

@section('content')
    <div class="jbw-address-grid">
        @forelse ($addresses as $address)
            <div class="jbw-card jbw-address-card" style="margin-top: 0px;">
                <div style="display:flex;justify-content:space-between;gap:0.75rem;align-items:start">
                    <span class="jbw-address-tag">{{ $address->label }}</span>
                    @if ($address->is_default)
                        <span style="font-size:0.6875rem;font-weight:800;color:var(--c-primary)">DEFAULT</span>
                    @endif
                </div>
                <p style="margin:0.5rem 0 0;line-height:1.6;color:var(--jbw-muted)">
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
            <div class="jbw-card jbw-address-card">
                <span class="jbw-address-tag">PROFILE</span>
                <p style="margin:0.5rem 0 0;line-height:1.6;color:var(--jbw-muted)">
                    {{ $customer->name }}<br>
                    {{ $customer->city ?? 'Add your city in profile' }}<br>
                    India
                </p>
            </div>
        @endforelse
    </div>

    <div class="jbw-card" style="margin-top:1.5rem">
        <p class="jbw-filter-title" style="margin-bottom:1rem">Add new address</p>
        <form method="POST" action="{{ route('web.profile.addresses.store') }}" class="jbw-form-stack">
            @csrf
            <div class="jbw-measure-form-grid" style="grid-template-columns:repeat(2,1fr)">
                <div class="jbw-field">
                    <label class="jbw-label" for="label">Label</label>
                    <select id="label" name="label" class="jbw-select" required>
                        <option value="HOME">Home</option>
                        <option value="WORK">Work</option>
                        <option value="OTHER">Other</option>
                    </select>
                </div>
                <div class="jbw-field">
                    <label class="jbw-label" for="name">Recipient name</label>
                    <input id="name" type="text" name="name" class="jbw-input" value="{{ old('name', $customer->name) }}">
                </div>
                <div class="jbw-field">
                    <label class="jbw-label" for="house_no">House / flat no.</label>
                    <input id="house_no" type="text" name="house_no" class="jbw-input" value="{{ old('house_no') }}" required>
                </div>
                <div class="jbw-field">
                    <label class="jbw-label" for="road_area">Street / area</label>
                    <input id="road_area" type="text" name="road_area" class="jbw-input" value="{{ old('road_area') }}" required>
                </div>
                <div class="jbw-field">
                    <label class="jbw-label" for="city">City</label>
                    <input id="city" type="text" name="city" class="jbw-input" value="{{ old('city', $customer->city) }}" required>
                </div>
                <div class="jbw-field">
                    <label class="jbw-label" for="state">State</label>
                    <input id="state" type="text" name="state" class="jbw-input" value="{{ old('state') }}">
                </div>
                <div class="jbw-field">
                    <label class="jbw-label" for="pincode">Pincode</label>
                    <input id="pincode" type="text" name="pincode" class="jbw-input" value="{{ old('pincode') }}" maxlength="10" required>
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
@endsection
