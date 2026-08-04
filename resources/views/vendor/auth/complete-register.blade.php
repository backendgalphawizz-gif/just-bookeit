@extends('vendor.layouts.guest')

@section('title', 'Vendor Registration')

@section('content')
@php $startStep = max(1, min(3, (int) ($initialStep ?? 1))); @endphp

<div class="vp-register-wizard" id="vp-register-wizard" data-step="{{ $startStep }}">
    <div class="vp-register-top">
        <button type="button" class="vp-register-back" id="vp-register-back" aria-label="Back">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <h1 class="vp-register-heading">Vendor Registration</h1>
        <span class="vp-register-step-label" id="vp-register-step-label">Step {{ $startStep }} of 3</span>
    </div>
    <div class="vp-register-progress" role="progressbar" aria-valuenow="{{ $startStep }}" aria-valuemin="1" aria-valuemax="3">
        <span class="vp-register-progress-fill" id="vp-register-progress-fill" style="width: {{ ($startStep / 3) * 100 }}%"></span>
    </div>

    <form method="POST" action="{{ route('vendor.register.submit') }}" enctype="multipart/form-data" class="vp-register-card" id="vp-register-form">
        @csrf
        <input type="hidden" name="registration_token" value="{{ $registerSession['registration_token'] }}">
        <input type="hidden" name="_step" id="vp-register-step-input" value="{{ $startStep }}">

        {{-- Step 1: Personal Details --}}
        <div class="vp-register-panel" data-step="1" @if ($startStep !== 1) hidden @endif>
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

            @php
                $maxImageBytes = \App\Support\VendorValidationRules::effectiveMaxImageBytes();
                $maxImageMb = \App\Support\UploadLimits::formatMegabytes($maxImageBytes);
            @endphp
            <div class="vp-field">
                <label class="vp-label">Aadhaar Card <span class="vp-required">*</span></label>
                <div class="vp-upload-grid">
                    <label class="vp-upload-tile">
                        <input type="file" name="aadhar_front" accept="image/jpeg,image/jpg,image/png,image/webp" class="vp-upload-input" data-vp-preview data-vp-required-step="1" data-vp-max-file-bytes="{{ $maxImageBytes }}" data-vp-file-label="Aadhaar front">
                        <span class="vp-upload-icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0l-4 4m4-4l4 4M4 20h16"/></svg>
                        </span>
                        <span class="vp-upload-title">Upload Front</span>
                        <span class="vp-upload-sub">JPEG/PNG, max {{ $maxImageMb }} MB</span>
                        <span class="vp-upload-name"></span>
                    </label>
                    <label class="vp-upload-tile">
                        <input type="file" name="aadhar_back" accept="image/jpeg,image/jpg,image/png,image/webp" class="vp-upload-input" data-vp-preview data-vp-required-step="1" data-vp-max-file-bytes="{{ $maxImageBytes }}" data-vp-file-label="Aadhaar back">
                        <span class="vp-upload-icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0l-4 4m4-4l4 4M4 20h16"/></svg>
                        </span>
                        <span class="vp-upload-title">Upload Back</span>
                        <span class="vp-upload-sub">JPEG/PNG, max {{ $maxImageMb }} MB</span>
                        <span class="vp-upload-name"></span>
                    </label>
                </div>
                <p class="vp-field-hint">Use clear JPEG/PNG photos under {{ $maxImageMb }} MB each. Large phone camera shots often fail — compress or resize first.</p>
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

        {{-- Step 2: Business + Location + Documents --}}
        <div class="vp-register-panel" data-step="2" @if ($startStep !== 2) hidden @endif>
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
                    <input id="business_mobile" type="tel" name="business_mobile" class="vp-input @error('business_mobile') vp-input--error @enderror" value="{{ old('business_mobile') }}" placeholder="10 digit mobile" inputmode="numeric" maxlength="10" pattern="[6-9][0-9]{9}" data-vp-restrict="phone">
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

            <h2 class="vp-register-panel-title" style="margin-top:1.35rem;">Location</h2>

            @php $mapsEnabled = filled(config('services.google.maps_api_key')); @endphp

            <div class="vp-field{{ $mapsEnabled ? ' vp-places-wrap' : '' }}">
                <label class="vp-label" for="address">Full Address</label>
                @if ($mapsEnabled)
                    <input
                        id="address"
                        type="text"
                        name="address"
                        class="vp-input vp-places-input @error('address') vp-input--error @enderror"
                        value="{{ old('address') }}"
                        placeholder="Start typing to search address…"
                        maxlength="500"
                        autocomplete="off"
                        data-vp-restrict="text"
                    >
                    <p class="vp-field-hint">Start typing to search and select an address.</p>
                    <input type="hidden" id="latitude" name="latitude" value="{{ old('latitude') }}">
                    <input type="hidden" id="longitude" name="longitude" value="{{ old('longitude') }}">
                @else
                    <textarea id="address" name="address" class="vp-input vp-textarea @error('address') vp-input--error @enderror" rows="3" placeholder="Shop/Building No, Street..." maxlength="500" data-vp-restrict="text">{{ old('address') }}</textarea>
                @endif
                @error('address')<p class="vp-field-error">{{ $message }}</p>@enderror
                @error('latitude')<p class="vp-field-error">{{ $message }}</p>@enderror
                @error('longitude')<p class="vp-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="vp-form-grid-2">
                <div class="vp-field">
                    <label class="vp-label" for="city">City</label>
                    <input id="city" type="text" name="city" class="vp-input @error('city') vp-input--error @enderror" value="{{ old('city') }}" placeholder="Mumbai" maxlength="100" data-vp-restrict="city">
                    @error('city')<p class="vp-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="vp-field">
                    <label class="vp-label" for="state">State</label>
                    <input id="state" type="text" name="state" class="vp-input @error('state') vp-input--error @enderror" value="{{ old('state') }}" placeholder="Maharashtra" maxlength="100" data-vp-restrict="city">
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
                    <input id="pincode" type="text" name="pincode" class="vp-input @error('pincode') vp-input--error @enderror" value="{{ old('pincode') }}" placeholder="400001" inputmode="numeric" maxlength="6" pattern="[1-9][0-9]{5}">
                    @error('pincode')<p class="vp-field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <h2 class="vp-register-panel-title" style="margin-top:1.35rem;">Documents</h2>
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

        {{-- Step 3: Bank Details --}}
        <div class="vp-register-panel" data-step="3" @if ($startStep !== 3) hidden @endif>
            <h2 class="vp-register-panel-title">Bank Details</h2>

            <div class="vp-field">
                <label class="vp-label" for="account_name">Account Holder Name <span class="vp-required">*</span></label>
                <input id="account_name" type="text" name="account_name" class="vp-input @error('account_name') vp-input--error @enderror" value="{{ old('account_name') }}" placeholder="Name as per bank records" required maxlength="255" data-vp-restrict="person-name">
                @error('account_name')<p class="vp-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="vp-field">
                <label class="vp-label" for="account_no">Account Number <span class="vp-required">*</span></label>
                <input id="account_no" type="text" name="account_no" class="vp-input @error('account_no') vp-input--error @enderror" value="{{ old('account_no') }}" placeholder="9–18 digits" required inputmode="numeric" minlength="9" maxlength="18" pattern="[0-9]{9,18}" data-vp-restrict="account-number" autocomplete="off">
                <p class="vp-field-hint">Enter 9–18 digits only. No spaces or letters.</p>
                @error('account_no')<p class="vp-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="vp-field">
                <label class="vp-label" for="bank_name">Bank Name <span class="vp-required">*</span></label>
                <input id="bank_name" type="text" name="bank_name" class="vp-input @error('bank_name') vp-input--error @enderror" value="{{ old('bank_name') }}" placeholder="State Bank of India" required maxlength="255" data-vp-restrict="title">
                @error('bank_name')<p class="vp-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="vp-field">
                <label class="vp-label" for="ifsc_code">IFSC Code <span class="vp-required">*</span></label>
                <input id="ifsc_code" type="text" name="ifsc_code" class="vp-input @error('ifsc_code') vp-input--error @enderror" value="{{ old('ifsc_code') }}" placeholder="SBIN0000001" required minlength="11" maxlength="11" pattern="[A-Za-z]{4}0[A-Za-z0-9]{6}" data-vp-restrict="ifsc" style="text-transform:uppercase" autocomplete="off">
                <p class="vp-field-hint">11 characters, e.g. SBIN0001234</p>
                @error('ifsc_code')<p class="vp-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="vp-field">
                <label class="vp-label">Account Type <span class="vp-required">*</span></label>
                <div class="vp-account-type">
                    <label class="vp-account-type-option">
                        <input type="radio" name="account_type" value="savings" @checked(old('account_type', 'savings') === 'savings') required>
                        <span>Saving</span>
                    </label>
                    <label class="vp-account-type-option">
                        <input type="radio" name="account_type" value="current" @checked(old('account_type') === 'current')>
                        <span>Current</span>
                    </label>
                </div>
                @error('account_type')<p class="vp-field-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="vp-register-footer">
            <p class="vp-register-remaining" id="vp-register-remaining">
                @if ($startStep >= 3)
                    Final Step
                @else
                    {{ 3 - $startStep }} step{{ (3 - $startStep) === 1 ? '' : 's' }} remaining
                @endif
            </p>
            <button type="button" class="vp-btn vp-btn--primary vp-register-continue" id="vp-register-continue">
                {{ $startStep >= 3 ? 'Submit Application' : 'Continue' }}
            </button>
        </div>
    </form>

    <p class="vp-auth-footer" style="text-align:center;margin-top:1rem;">
        Wrong number? <a href="{{ route('vendor.register') }}">Change mobile</a>
    </p>
</div>

<script>
(function () {
    const wizard = document.getElementById('vp-register-wizard');
    const form = document.getElementById('vp-register-form');
    if (!wizard || !form) return;

    const total = 3;
    let step = Number(wizard.dataset.step || 1);

    const stepLabel = document.getElementById('vp-register-step-label');
    const progressFill = document.getElementById('vp-register-progress-fill');
    const stepInput = document.getElementById('vp-register-step-input');
    const remainingEl = document.getElementById('vp-register-remaining');
    const continueBtn = document.getElementById('vp-register-continue');
    const backBtn = document.getElementById('vp-register-back');
    const panels = Array.from(wizard.querySelectorAll('.vp-register-panel'));

    function setStep(next) {
        step = Math.min(total, Math.max(1, next));
        wizard.dataset.step = String(step);
        stepInput.value = String(step);
        stepLabel.textContent = 'Step ' + step + ' of ' + total;
        progressFill.style.width = ((step / total) * 100) + '%';
        progressFill.parentElement.setAttribute('aria-valuenow', String(step));

        const left = total - step;
        remainingEl.textContent = left === 0
            ? 'Final Step'
            : (left + ' step' + (left === 1 ? '' : 's') + ' remaining');

        panels.forEach((panel) => {
            panel.hidden = Number(panel.dataset.step) !== step;
        });

        continueBtn.textContent = step >= total ? 'Submit Application' : 'Continue';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function fieldError(field, message) {
        field.setCustomValidity(message || '');
        if (message) {
            field.reportValidity();
        }
    }

    function validateCurrentStep() {
        const panel = wizard.querySelector('.vp-register-panel[data-step="' + step + '"]');
        if (!panel) return true;

        const fields = panel.querySelectorAll('input, select, textarea');
        for (const field of fields) {
            if (field.type === 'radio' || field.type === 'checkbox' || field.type === 'file' || field.type === 'hidden') {
                continue;
            }

            fieldError(field, '');

            if (!field.checkValidity()) {
                field.reportValidity();
                return false;
            }
        }

        if (step === 1) {
            const front = form.querySelector('input[name="aadhar_front"]');
            const back = form.querySelector('input[name="aadhar_back"]');
            if (!front?.files?.length) {
                alert('Please upload Aadhaar front image.');
                return false;
            }
            if (!back?.files?.length) {
                alert('Please upload Aadhaar back image.');
                return false;
            }
        }

        if (step === 2) {
            const checked = panel.querySelectorAll('input[name="service_types[]"]:checked');
            if (!checked.length) {
                alert('Please select at least one service type.');
                return false;
            }

            const aadharNumber = panel.querySelector('#aadhar_number');
            if (aadharNumber && aadharNumber.value.trim() !== '' && !/^[2-9][0-9]{11}$/.test(aadharNumber.value.trim())) {
                fieldError(aadharNumber, 'Enter a valid 12-digit Aadhaar number.');
                return false;
            }

            const gst = panel.querySelector('#gst_no');
            if (gst && gst.value.trim() !== '' && gst.value.trim().length !== 15) {
                fieldError(gst, 'GSTIN must be exactly 15 characters.');
                return false;
            }

            const pincode = panel.querySelector('#pincode');
            if (pincode && pincode.value.trim() !== '' && !/^[1-9][0-9]{5}$/.test(pincode.value.trim())) {
                fieldError(pincode, 'Enter a valid 6-digit pincode.');
                return false;
            }
        }

        if (step === 3) {
            const accountNo = panel.querySelector('#account_no');
            const ifsc = panel.querySelector('#ifsc_code');
            const accountType = panel.querySelector('input[name="account_type"]:checked');

            if (accountNo) {
                const digits = String(accountNo.value || '').replace(/\D/g, '');
                accountNo.value = digits;
                if (!/^[0-9]{9,18}$/.test(digits)) {
                    fieldError(accountNo, 'Account number must be 9–18 digits only.');
                    return false;
                }
                fieldError(accountNo, '');
            }

            if (ifsc) {
                const code = String(ifsc.value || '').toUpperCase().trim();
                ifsc.value = code;
                if (!/^[A-Z]{4}0[A-Z0-9]{6}$/.test(code)) {
                    fieldError(ifsc, 'Enter a valid IFSC code (e.g. SBIN0001234).');
                    return false;
                }
                fieldError(ifsc, '');
            }

            if (!accountType) {
                alert('Please select an account type.');
                return false;
            }
        }

        return true;
    }

    continueBtn.addEventListener('click', function () {
        if (!validateCurrentStep()) return;

        if (step < total) {
            setStep(step + 1);
            return;
        }

        form.requestSubmit();
    });

    backBtn.addEventListener('click', function () {
        // Keep filled steps/images — only move within the wizard.
        if (step === 1) {
            return;
        }
        setStep(step - 1);
    });

    form.addEventListener('submit', function (event) {
        if (step < total) {
            event.preventDefault();
            if (validateCurrentStep()) setStep(step + 1);
            return;
        }

        // Final submit: validate every step so bank errors don't lose earlier uploads.
        for (let s = 1; s <= total; s++) {
            step = s;
            if (!validateCurrentStep()) {
                event.preventDefault();
                setStep(s);
                return;
            }
        }
    });

    document.querySelectorAll('[data-vp-preview]').forEach((input) => {
        input.addEventListener('change', () => {
            const nameEl = input.closest('.vp-upload-tile')?.querySelector('.vp-upload-name');
            const file = input.files?.[0];
            const maxBytes = Number(input.dataset.vpMaxFileBytes || 0);
            const label = input.dataset.vpFileLabel || 'Image';

            if (file && maxBytes > 0 && file.size > maxBytes) {
                const maxMb = (maxBytes / (1024 * 1024)).toFixed(1).replace(/\.0$/, '');
                alert(label + ' is too large (' + (file.size / (1024 * 1024)).toFixed(1) + ' MB). Maximum is ' + maxMb + ' MB. Please compress or choose a smaller file.');
                input.value = '';
                if (nameEl) nameEl.textContent = '';
                return;
            }

            if (nameEl) {
                nameEl.textContent = file?.name || '';
            }
        });
    });

    setStep(step);
})();
</script>
@include('vendor.partials.form-file-persistence')

@if (filled(config('services.google.maps_api_key')))
@push('styles')
<style>
    .vp-places-wrap { position: relative; z-index: 30; }
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
    .pac-container.vp-places-hidden {
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
    .pac-item:first-child { border-top: 0; }
    .pac-item:hover,
    .pac-item-selected { background: #fff7ed; }
    .pac-item-query { font-weight: 600; color: #0f172a; }
</style>
@endpush
@push('scripts')
<script src="{{ asset('js/vendor-google-places.js') }}?v={{ @filemtime(public_path('js/vendor-google-places.js')) }}"></script>
<script
    src="https://maps.googleapis.com/maps/api/js?key={{ urlencode(config('services.google.maps_api_key')) }}&libraries=places&callback=initVendorGooglePlaces"
    async
    defer
></script>
@endpush
@endif
@endsection
