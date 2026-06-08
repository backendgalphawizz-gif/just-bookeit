@extends('vendor.layouts.guest')

@section('title', 'Verify OTP')

@section('content')
<div class="vp-auth-card">
    @include('vendor.partials.auth-logo')
    <p class="vp-auth-kicker">Vendor Partner</p>
    <h1 class="vp-auth-title">Verify OTP</h1>
    <p class="vp-auth-sub">We've sent a 4-digit code to your number</p>

    <form method="POST" action="{{ route('vendor.verify-otp.submit') }}" class="vp-auth-form">
        @csrf
        <div class="vp-field">
            <label class="vp-label" for="otp">4-digit OTP <span class="vp-required">*</span></label>
            <input id="otp" type="text" name="otp" class="vp-input vp-otp-input @error('otp') vp-input--error @enderror" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" data-vp-restrict="otp" placeholder="••••" required autofocus>
            @error('otp')<p class="vp-field-error">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="vp-btn vp-btn--primary vp-btn--block" style="padding:.85rem;">Verify &amp; Proceed</button>
    </form>

    <p class="vp-auth-footer">
        Didn't receive code? <a href="{{ route('vendor.login') }}">Resend</a>
    </p>
</div>
@endsection
