@extends('vendor.layouts.guest')

@section('title', 'Vendor Registration')

@section('content')
<div
    class="vp-register-wizard"
    x-data="{
        step: {{ (int) $initialStep }},
        total: 3,
        go(n) { this.step = Math.min(this.total, Math.max(1, n)); window.scrollTo({ top: 0, behavior: 'smooth' }); },
        next() {
            const panel = this.$root.querySelector('.vp-register-panel[data-step=\"' + this.step + '\"]');
            if (panel) {
                const fields = panel.querySelectorAll('input, select, textarea');
                for (const field of fields) {
                    if (!field.checkValidity()) {
                        field.reportValidity();
                        return;
                    }
                }
                if (this.step === 2) {
                    const checked = panel.querySelectorAll('input[name=\"service_types[]\"]:checked');
                    if (!checked.length) {
                        alert('Please select at least one service type.');
                        return;
                    }
                }
            }
            this.go(this.step + 1);
        },
        prev() { if (this.step === 1) { window.location.href = '{{ route('vendor.register') }}'; return; } this.go(this.step - 1); },
        remaining() { return this.total - this.step; }
    }"
>
    <div class="vp-register-top">
        <button type="button" class="vp-register-back" @click="prev()" aria-label="Back">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <h1 class="vp-register-heading">Vendor Registration</h1>
        <span class="vp-register-step-label" x-text="'Step ' + step + ' of ' + total"></span>
    </div>
    <div class="vp-register-progress" role="progressbar" :aria-valuenow="step" aria-valuemin="1" aria-valuemax="3">
        <span class="vp-register-progress-fill" :style="'width:' + ((step / total) * 100) + '%'"></span>
    </div>

    <form method="POST" action="{{ route('vendor.register.submit') }}" enctype="multipart/form-data" class="vp-register-card" id="vp-register-form">
        @csrf
        <input type="hidden" name="registration_token" value="{{ $registerSession['registration_token'] }}">
        <input type="hidden" name="_step" :value="step">

        {{-- Step 1: Personal Details --}}
        <div class="vp-register-panel" data-step="1" x-show="step === 1" x-cloak>
            <h2 class="vp-register-panel-title">Personal Details</h2>

            <div class="vp-field">
                <label class="vp-label" for="owner_name">Full Name <span class="vp-required">*</span></label>
                <input id="owner_name" type="text" name="owner_name" class="vp-input @error('owner_name') vp-input--error @enderror" value="{{ old('owner_name') }}" placeholder="Enter your full name" required maxlength="100" data-vp-restrict="person-name" autocomplete="name">
                @error('owner_name')<p class="vp-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="vp-field">
                <label class="vp-label" for="mobile_display">Mobile Number <span class="vp-required">*</span></label>
                <input id="mobile_display" type="text" class="vp-input" value="{{ $maskedMobile }}" readonly>
                <p class="vp-field-hint">Verified via OTP</p>
            </div>

            <div class="vp-field">
                <label class="vp-label" for="email">Email Address <span class="vp-required">*</span></label>
                <input id="email" type="email" name="email" class="vp-input @error('email') vp-input--error @enderror" value="{{ old('email') }}" placeholder="you@example.com" required maxlength="255" data-vp-restrict="email" autocomplete="email">
                @error('email')<p class="vp-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="vp-field">
                <label class="vp-label">Aadhaar Card <span class="vp-required">*</span></label>
                <div class="vp-upload-grid">
                    <label class="vp-upload-tile">
                        <input type="file" name="aadhar_front" accept="image/jpeg,image/jpg,image/png,image/webp" required class="vp-upload-input" data-vp-preview>
                        <span class="vp-upload-icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0l-4 4m4-4l4 4M4 20h16"/></svg>
                        </span>
                        <span class="vp-upload-title">Upload Front</span>
                        <span class="vp-upload-sub">JPEG, PNG</span>
                        <span class="vp-upload-name"></span>
                    </label>
                    <label class="vp-upload-tile">
                        <input type="file" name="aadhar_back" accept="image/jpeg,image/jpg,image/png,image/webp" required class="vp-upload-input" data-vp-preview>
                        <span class="vp-upload-icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0l-4 4m4-4l4 4M4 20h16"/></svg>
                        </span>
                        <span class="vp-upload-title">Upload Back</span>
                        <span class="vp-upload-sub">JPEG, PNG</span>
                        <span class="vp-upload-name"></span>
                    </label>
                </div>
                @error('aadhar_front')<p class="vp-field-error">{{ $message }}</p>@enderror
                @error('aadhar_back')<p class="vp-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="vp-field">
                <label class="vp-label">Cover &amp; Profile Image</label>
                <div class="vp-upload-grid">
                    <label class="vp-upload-tile">
                        <input type="file" name="cover_image" accept="image/jpeg,image/jpg,image/png,image/webp" class="vp-upload-input" data-vp-preview>
                        <span class="vp-upload-icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0l-4 4m4-4l4 4M4 20h16"/></svg>
                        </span>
                        <span class="vp-upload-title">Upload Cover Image</span>
                        <span class="vp-upload-sub">JPEG, PNG</span>
                        <span class="vp-upload-name"></span>
                    </label>
                    <label class="vp-upload-tile">
                        <input type="file" name="profile_image" accept="image/jpeg,image/jpg,image/png,image/webp" class="vp-upload-input" data-vp-preview>
                        <span class="vp-upload-icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0l-4 4m4-4l4 4M4 20h16"/></svg>
                        </span>
                        <span class="vp-upload-title">Upload Profile Image</span>
                        <span class="vp-upload-sub">JPEG, PNG</span>
                        <span class="vp-upload-name"></span>
                    </label>
                </div>
                @error('cover_image')<p class="vp-field-error">{{ $message }}</p>@enderror
                @error('profile_image')<p class="vp-field-error">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- Step 2: Business Details --}}
        <div class="vp-register-panel" data-step="2" x-show="step === 2" x-cloak>
            <h2 class="vp-register-panel-title">Business Details</h2>

            <div class="vp-field">
                <label class="vp-label" for="shop_name">Shop/Business Name <span class="vp-required">*</span></label>
                <input id="shop_name" type="text" name="shop_name" class="vp-input @error('shop_name') vp-input--error @enderror" value="{{ old('shop_name') }}" placeholder="E.g. Royal Boutique" required maxlength="100" data-vp-restrict="title">
                @error('shop_name')<p class="vp-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="vp-field">
                <label class="vp-label">Service Type <span class="vp-required">*</span> <span style="font-weight:500;color:var(--vp-muted);">(Select multiple)</span></label>
                <div class="vp-service-stack">
                    @foreach ($serviceOptions as $option)
                        <label class="vp-service-row">
                            <input type="checkbox" name="service_types[]" value="{{ $option }}" @checked(in_array($option, old('service_types', []), true))>
                            <span>{{ $option }}</span>
                        </label>
                    @endforeach
                </div>
                @error('service_types')<p class="vp-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="vp-form-grid-2">
                <div class="vp-field">
                    <label class="vp-label" for="business_mobile">Business Mobile</label>
                    <input id="business_mobile" type="tel" name="business_mobile" class="vp-input @error('business_mobile') vp-input--error @enderror" value="{{ old('business_mobile') }}" placeholder="+91 00000 00000" inputmode="numeric" maxlength="10" pattern="[0-9]{10}" data-vp-restrict="phone">
                    @error('business_mobile')<p class="vp-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="vp-field">
                    <label class="vp-label" for="business_mail">Business Email</label>
                    <input id="business_mail" type="email" name="business_mail" class="vp-input @error('business_mail') vp-input--error @enderror" value="{{ old('business_mail') }}" placeholder="shop@example.com" maxlength="255" data-vp-restrict="email">
                    @error('business_mail')<p class="vp-field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="vp-field">
                <label class="vp-label" for="aadhar_number">Aadhaar Number</label>
                <input id="aadhar_number" type="text" name="aadhar_number" class="vp-input @error('aadhar_number') vp-input--error @enderror" value="{{ old('aadhar_number') }}" placeholder="123654789852" inputmode="numeric" maxlength="12" pattern="[0-9]{12}">
                @error('aadhar_number')<p class="vp-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="vp-field">
                <label class="vp-label" for="gst_no">GSTIN</label>
                <input id="gst_no" type="text" name="gst_no" class="vp-input @error('gst_no') vp-input--error @enderror" value="{{ old('gst_no') }}" placeholder="22AAAAA0000A1Z5" maxlength="15" data-vp-restrict="gst" style="text-transform:uppercase">
                @error('gst_no')<p class="vp-field-error">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- Step 3: Location & Documents --}}
        <div class="vp-register-panel" data-step="3" x-show="step === 3" x-cloak>
            <h2 class="vp-register-panel-title">Location</h2>

            <div class="vp-field">
                <label class="vp-label" for="address">Full Address</label>
                <textarea id="address" name="address" class="vp-input vp-textarea @error('address') vp-input--error @enderror" rows="3" placeholder="Shop/Building No, Street..." maxlength="500" data-vp-restrict="text">{{ old('address') }}</textarea>
                @error('address')<p class="vp-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="vp-form-grid-2">
                <div class="vp-field">
                    <label class="vp-label" for="city">City</label>
                    <input id="city" type="text" name="city" class="vp-input @error('city') vp-input--error @enderror" value="{{ old('city', 'Mumbai') }}" maxlength="100" data-vp-restrict="city">
                    @error('city')<p class="vp-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="vp-field">
                    <label class="vp-label" for="state">State</label>
                    <input id="state" type="text" name="state" class="vp-input @error('state') vp-input--error @enderror" value="{{ old('state', 'Maharashtra') }}" maxlength="100" data-vp-restrict="city">
                    @error('state')<p class="vp-field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="vp-form-grid-2">
                <div class="vp-field">
                    <label class="vp-label" for="country">Country</label>
                    <input id="country" type="text" name="country" class="vp-input @error('country') vp-input--error @enderror" value="{{ old('country', 'India') }}" maxlength="100" data-vp-restrict="city">
                    @error('country')<p class="vp-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="vp-field">
                    <label class="vp-label" for="pincode">Pincode</label>
                    <input id="pincode" type="text" name="pincode" class="vp-input @error('pincode') vp-input--error @enderror" value="{{ old('pincode', '400001') }}" inputmode="numeric" maxlength="6" pattern="[1-9][0-9]{5}">
                    @error('pincode')<p class="vp-field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <h2 class="vp-register-panel-title" style="margin-top:1.25rem;">Documents</h2>
            <div class="vp-upload-grid">
                <label class="vp-upload-tile">
                    <input type="file" name="shop_logo" accept="image/jpeg,image/jpg,image/png,image/webp" class="vp-upload-input" data-vp-preview>
                    <span class="vp-upload-icon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0l-4 4m4-4l4 4M4 20h16"/></svg>
                    </span>
                    <span class="vp-upload-title">Shop Logo</span>
                    <span class="vp-upload-sub">JPEG, PNG</span>
                    <span class="vp-upload-name"></span>
                </label>
                <label class="vp-upload-tile">
                    <input type="file" name="pan_card" accept="image/jpeg,image/jpg,image/png,image/webp" class="vp-upload-input" data-vp-preview>
                    <span class="vp-upload-icon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0l-4 4m4-4l4 4M4 20h16"/></svg>
                    </span>
                    <span class="vp-upload-title">PAN Card</span>
                    <span class="vp-upload-sub">JPEG, PNG</span>
                    <span class="vp-upload-name"></span>
                </label>
            </div>
            @error('shop_logo')<p class="vp-field-error">{{ $message }}</p>@enderror
            @error('pan_card')<p class="vp-field-error">{{ $message }}</p>@enderror
        </div>

        <div class="vp-register-footer">
            <p class="vp-register-remaining" x-text="remaining() === 0 ? 'Last step' : (remaining() + ' step' + (remaining() === 1 ? '' : 's') + ' remaining')"></p>
            <button type="button" class="vp-btn vp-btn--primary vp-register-continue" x-show="step < total" @click="next()">Continue</button>
            <button type="submit" class="vp-btn vp-btn--primary vp-register-continue" x-show="step === total" x-cloak>Submit</button>
        </div>
    </form>
</div>

<script>
    document.querySelectorAll('[data-vp-preview]').forEach((input) => {
        input.addEventListener('change', () => {
            const nameEl = input.closest('.vp-upload-tile')?.querySelector('.vp-upload-name');
            if (nameEl) {
                nameEl.textContent = input.files?.[0]?.name || '';
            }
        });
    });
</script>
@endsection
