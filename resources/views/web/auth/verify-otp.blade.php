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

        <p class="jbw-auth-resend">
            <a href="{{ $otpSession['type'] === 'register' ? route('web.register') : route('web.login') }}" id="resend-link">Request new code</a>
            <span id="resend-timer" class="jbw-auth-timer">00:48</span>
        </p>
    </x-web.auth-shell>

    <script>
        (function () {
            const boxes = Array.from(document.querySelectorAll('[data-otp-box]'));
            const hidden = document.getElementById('otp-combined');
            const form = document.getElementById('otp-form');
            const timerEl = document.getElementById('resend-timer');
            const resendLink = document.getElementById('resend-link');
            let seconds = 48;

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

            const tick = setInterval(() => {
                seconds = Math.max(0, seconds - 1);
                timerEl.textContent = String(Math.floor(seconds / 60)).padStart(2, '0') + ':' + String(seconds % 60).padStart(2, '0');
                if (seconds === 0) {
                    clearInterval(tick);
                    resendLink.style.pointerEvents = 'auto';
                    resendLink.style.opacity = '1';
                }
            }, 1000);
            resendLink.style.pointerEvents = 'none';
            resendLink.style.opacity = '0.5';
        })();
    </script>
@endsection
