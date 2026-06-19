@extends('vendor.layouts.guest')

@section('title', 'Verify OTP')

@section('content')
<div class="vp-auth-card">
    @include('vendor.partials.auth-logo')
    <p class="vp-auth-kicker">Vendor Partner</p>
    <h1 class="vp-auth-title">Verify OTP</h1>
    <p class="vp-auth-sub">We've sent a 4-digit code to <strong>{{ $maskedMobile }}</strong></p>

    <form method="POST" action="{{ route('vendor.verify-otp.submit') }}" class="vp-auth-form" id="vp-otp-form">
        @csrf
        <div class="vp-field">
            <label class="vp-label" for="otp">4-digit OTP <span class="vp-required">*</span></label>
            <input id="otp" type="text" name="otp" class="vp-input vp-otp-input @error('otp') vp-input--error @enderror" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" data-vp-restrict="otp" placeholder="••••" required autofocus autocomplete="one-time-code">
            @error('otp')<p class="vp-field-error">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="vp-btn vp-btn--primary vp-btn--block" style="padding:.85rem;">Verify &amp; Proceed</button>
    </form>

    <div class="vp-auth-resend">
        <form method="POST" action="{{ route('vendor.otp.resend') }}" id="vp-resend-form">
            @csrf
            <button
                type="submit"
                class="vp-auth-resend-btn"
                id="vp-resend-btn"
                @if ($resendIn > 0) disabled @endif
            >
                Resend OTP
            </button>
        </form>
        <span class="vp-auth-timer" id="vp-resend-timer" @if ($resendIn <= 0) hidden @endif>
            {{ sprintf('%02d:%02d', intdiv($resendIn, 60), $resendIn % 60) }}
        </span>
    </div>

    <p class="vp-auth-footer">
        Wrong number?
        <a href="{{ $otpSession['type'] === 'register' ? route('vendor.register') : route('vendor.login') }}">Change mobile</a>
    </p>
</div>

<script>
    (function () {
        const form = document.getElementById('vp-resend-form');
        const button = document.getElementById('vp-resend-btn');
        const timerEl = document.getElementById('vp-resend-timer');
        let seconds = {{ (int) $resendIn }};

        if (!form || !button || seconds <= 0) {
            return;
        }

        const tick = window.setInterval(() => {
            seconds = Math.max(0, seconds - 1);

            if (seconds > 0) {
                timerEl.textContent = String(Math.floor(seconds / 60)).padStart(2, '0') + ':' + String(seconds % 60).padStart(2, '0');
                return;
            }

            window.clearInterval(tick);
            button.disabled = false;
            button.classList.remove('is-disabled');
            timerEl.hidden = true;
        }, 1000);

        button.classList.add('is-disabled');
    })();
</script>
@endsection
