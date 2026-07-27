@extends('vendor.layouts.guest')

@section('title', 'Vendor Sign Up')

@section('content')
<div class="vp-auth-card vp-auth-card--login">
    @include('vendor.partials.auth-logo')

    <p class="vp-auth-brand">Vendor Partner</p>
    <h1 class="vp-auth-title">Create account</h1>
    <p class="vp-auth-sub">Enter your mobile number to get started</p>

    <form method="POST" action="{{ route('vendor.otp.send') }}" class="vp-auth-form">
        @csrf
        <input type="hidden" name="type" value="register">

        <div class="vp-field">
            <label class="vp-label" for="mobile">Mobile Number</label>
            <div class="vp-mobile-input @error('mobile') vp-mobile-input--error @enderror">
                <span class="vp-mobile-prefix" aria-hidden="true">+91</span>
                <input
                    id="mobile"
                    type="tel"
                    name="mobile"
                    class="vp-mobile-field @error('mobile') vp-input--error @enderror"
                    value="{{ old('mobile') }}"
                    placeholder="Enter 10 digit number"
                    inputmode="numeric"
                    maxlength="10"
                    pattern="[0-9]{10}"
                    data-vp-restrict="phone"
                    required
                    autofocus
                    autocomplete="tel"
                >
            </div>
            @error('mobile')<p class="vp-field-error">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="vp-btn vp-btn--primary vp-btn--block vp-auth-continue">Send OTP</button>
    </form>

    <p class="vp-auth-footer">
        Already registered? <a href="{{ route('vendor.login') }}">Sign in</a>
    </p>
</div>
@endsection
