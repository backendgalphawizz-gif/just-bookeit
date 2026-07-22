@extends('web.layouts.guest')

@section('title', 'Verify OTP')

@section('content')
    <x-web.auth-shell title="Get Verified" subtitle="Enter the 4-digit code sent to +91 ******{{ substr($otpSession['mobile'], -3) }}" :centered="true">
        <form method="POST" action="{{ route('web.verify-otp.submit') }}" class="jbw-form-stack jbw-form-stack--otp" id="otp-form">
            @csrf
            <div class="jbw-otp-row">
                @foreach (range(1, 4) as $i)
                    <input type="text" name="otp_digits[]" placeholder="-" class="jbw-otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" data-otp-box autocomplete="one-time-code" aria-label="Digit {{ $i }}" @if($i===1) autofocus @endif>
                @endforeach
            </div>
            <input type="hidden" name="otp" id="otp-combined">
            <button type="submit" class="jbw-btn jbw-btn--primary jbw-btn--block jbw-btn--cta">
                {{ $otpSession['type'] === 'register' ? 'Sign Up' : 'Login' }}
            </button>
        </form>

        <div class="jbw-auth-resend">
            <form method="POST" action="{{ route('web.otp.resend') }}" id="resend-form">
                @csrf
                <button
                    type="submit"
                    class="jbw-auth-resend-btn"
                    id="resend-btn"
                    @if (($resendIn ?? 0) > 0) disabled @endif
                >
                    Resend OTP
                </button>
            </form>
            <span id="resend-timer" class="jbw-auth-timer" @if (($resendIn ?? 0) <= 0) hidden @endif>
                {{ sprintf('%02d:%02d', intdiv((int) ($resendIn ?? 0), 60), ((int) ($resendIn ?? 0)) % 60) }}
            </span>
        </div>

        <a href="{{ route('web.login') }}" class="jbw-auth-footer textmanage">
            <p>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5"></path>
                    <path d="M12 19L5 12L12 5"></path>
                </svg>
                Back to Login
            </p>
        </a>

        <a href="{{ url('/') }}" class="jbw-auth-footer textmanage">
            <p>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5"></path>
                    <path d="M12 19L5 12L12 5"></path>
                </svg>
                Go To Home
            </p>
        </a>
    </x-web.auth-shell>

    <script>
        (function () {
            const boxes = Array.from(document.querySelectorAll('[data-otp-box]'));
            const hidden = document.getElementById('otp-combined');
            const form = document.getElementById('otp-form');
            const resendBtn = document.getElementById('resend-btn');
            const timerEl = document.getElementById('resend-timer');
            let seconds = {{ (int) ($resendIn ?? 0) }};

            boxes.forEach((box, index) => {
                box.addEventListener('input', () => {
                    box.value = box.value.replace(/\D/g, '').slice(-1);
                    if (box.value) box.classList.add('is-filled');
                    else box.classList.remove('is-filled');
                    if (box.value && boxes[index + 1]) boxes[index + 1].focus();
                    hidden.value = boxes.map(b => b.value).join('');
                });
                box.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && !box.value && boxes[index - 1]) boxes[index - 1].focus();
                });
                box.addEventListener('focus', () => box.select());
            });

            form.addEventListener('submit', (e) => {
                hidden.value = boxes.map(b => b.value).join('');
                if (hidden.value.length !== 4) {
                    e.preventDefault();
                    alert('Please enter the 4-digit OTP.');
                }
            });

            if (!resendBtn || !timerEl || seconds <= 0) {
                return;
            }

            resendBtn.disabled = true;
            resendBtn.classList.add('is-disabled');

            const tick = setInterval(() => {
                seconds = Math.max(0, seconds - 1);
                timerEl.textContent = String(Math.floor(seconds / 60)).padStart(2, '0') + ':' + String(seconds % 60).padStart(2, '0');

                if (seconds === 0) {
                    clearInterval(tick);
                    resendBtn.disabled = false;
                    resendBtn.classList.remove('is-disabled');
                    timerEl.hidden = true;
                }
            }, 1000);
        })();
    </script>
@endsection
