@extends('vendor.layouts.guest')

@section('title', 'Vendor Sign up')

@section('content')
<div class="vp-auth-card">
    @include('vendor.partials.auth-logo')
    <p class="vp-auth-kicker">Vendor Partner</p>
    <h1 class="vp-auth-title">Create Account</h1>
    <p class="vp-auth-sub">Enter your mobile number to get started</p>

    <form method="POST" action="{{ route('vendor.otp.send') }}" class="vp-auth-form">
        @csrf
        <input type="hidden" name="type" value="register">
        <div class="vp-field">
            <label class="vp-label" for="mobile">Mobile Number <span class="vp-required">*</span></label>
            <input id="mobile" type="tel" name="mobile" class="vp-input @error('mobile') vp-input--error @enderror" value="{{ old('mobile') }}" placeholder="10 digit mobile number" inputmode="numeric" maxlength="10" pattern="[0-9]{10}" data-vp-restrict="phone" required autofocus autocomplete="tel">
            <p class="vp-field-hint">10 digits only, no +91 prefix</p>
            @error('mobile')<p class="vp-field-error">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="vp-btn vp-btn--primary vp-btn--block" style="padding:.85rem;">Get OTP</button>
    </form>

    <p class="vp-auth-footer">
        Already registered? <a href="{{ route('vendor.login') }}">Sign in</a>
    </p>
</div>
@endsection
