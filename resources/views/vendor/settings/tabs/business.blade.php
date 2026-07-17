<h2 class="vp-settings-panel-title">Business Details</h2>

@php $selectedServices = old('service_types', \App\Support\VendorValidationRules::normalizeServiceTypes($vendor->selectedServiceTypes())); @endphp

<form method="POST" action="{{ route('vendor.settings.update') }}">
    @csrf
    <input type="hidden" name="tab" value="business">

    <div class="vp-field">
        <label class="vp-label" for="shop_name">Shop/Business Name <span class="vp-required">*</span></label>
        <input id="shop_name" type="text" name="shop_name" class="vp-input @error('shop_name') vp-input--error @enderror" value="{{ old('shop_name', $vendor->shop_name) }}" placeholder="E.g. Royal Boutique" required maxlength="100" data-vp-restrict="title">
        @error('shop_name')<p class="vp-field-error">{{ $message }}</p>@enderror
    </div>

    <div class="vp-field">
        <label class="vp-label">Service Type <span class="vp-required">*</span> <span style="font-weight:500;color:var(--vp-muted);">(Select at least one)</span></label>
        <div class="vp-service-chips">
            @foreach ($serviceOptions as $option)
                <label class="vp-service-chip">
                    <input type="checkbox" name="service_types[]" value="{{ $option }}" @checked(in_array($option, $selectedServices))>
                    <span>{{ $option }}</span>
                </label>
            @endforeach
        </div>
        @error('service_types')<p class="vp-field-error">{{ $message }}</p>@enderror
    </div>

    <div class="vp-form-grid-2">
        <div class="vp-field">
            <label class="vp-label" for="business_mobile">Business Mobile</label>
            <input id="business_mobile" type="tel" name="business_mobile" class="vp-input @error('business_mobile') vp-input--error @enderror" value="{{ old('business_mobile', $vendor->business_mobile) }}" placeholder="10 digit number" inputmode="numeric" maxlength="10" pattern="[0-9]{10}" data-vp-restrict="phone">
            @error('business_mobile')<p class="vp-field-error">{{ $message }}</p>@enderror
        </div>
        <div class="vp-field">
            <label class="vp-label" for="business_mail">Business Email</label>
            <input id="business_mail" type="email" name="business_mail" class="vp-input @error('business_mail') vp-input--error @enderror" value="{{ old('business_mail', $vendor->business_email) }}" placeholder="shop@example.com" maxlength="255" data-vp-restrict="email">
            @error('business_mail')<p class="vp-field-error">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="vp-field">
        <label class="vp-label" for="gst_no">GSTIN</label>
        <input id="gst_no" type="text" name="gst_no" class="vp-input @error('gst_no') vp-input--error @enderror" value="{{ old('gst_no', $vendor->gst_number) }}" placeholder="22AAAAA0000A1Z5" maxlength="15" data-vp-restrict="gst">
        <p class="vp-field-hint">15-character GSTIN (optional)</p>
        @error('gst_no')<p class="vp-field-error">{{ $message }}</p>@enderror
    </div>

    <div class="vp-section-title">Location</div>
    <div class="vp-field">
        <label class="vp-label" for="address">Full Address</label>
        <textarea id="address" name="address" class="vp-textarea @error('address') vp-textarea--error @enderror" rows="3" maxlength="500" data-vp-restrict="text" placeholder="Shop/Building No, Street...">{{ old('address', $vendor->address) }}</textarea>
        @error('address')<p class="vp-field-error">{{ $message }}</p>@enderror
    </div>

    <div class="vp-settings-panel-foot">
        <button type="submit" class="vp-btn vp-btn--primary">Save</button>
    </div>
</form>
