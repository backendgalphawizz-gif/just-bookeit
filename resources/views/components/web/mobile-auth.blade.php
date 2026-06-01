@props(['mode' => 'login'])

@php
    $isLogin = $mode === 'login';
    $title = $isLogin ? 'Sign in to your Account' : 'Sign up to your Account';
    $footerPrompt = $isLogin ? "Don't have an account?" : 'Already have an account?';
    $footerHref = $isLogin ? route('web.register') : route('web.login');
    $footerLink = $isLogin ? 'Register Now' : 'Sign in';
    $otpType = $isLogin ? 'login' : 'register';
@endphp

<x-web.auth-shell :title="$title" subtitle="Enter your mobile number for verification">
    <form method="POST" action="{{ route('web.login.otp') }}" class="jbw-form-stack" id="mobile-auth-form" novalidate>
        @csrf
        <input type="hidden" name="type" value="{{ $otpType }}">

        <div class="jbw-field">
            <label class="jbw-label" for="mobile">Mobile number</label>
            <input
                id="mobile"
                type="tel"
                name="mobile"
                class="jbw-input jbw-input--auth @error('mobile') is-invalid @enderror"
                value="{{ old('mobile') }}"
                placeholder="+91 9512345678"
                inputmode="tel"
                autocomplete="tel"
                maxlength="14"
                required
                autofocus
            >
            @error('mobile')
                <p class="jbw-field-error" role="alert">{{ $message }}</p>
            @enderror
            <p class="jbw-field-hint" id="mobile-hint" hidden></p>
        </div>

        <button type="submit" class="jbw-btn jbw-btn--primary jbw-btn--block jbw-btn--cta">Get OTP</button>
    </form>

    <div class="jbw-auth-divider"><span>or</span></div>

    <div class="jbw-form-stack">
        <button type="button" class="jbw-btn jbw-btn--social jbw-btn--block" disabled>
            <span class="jbw-social-icon jbw-social-icon--google">G</span> Continue with Google
        </button>
        <button type="button" class="jbw-btn jbw-btn--social jbw-btn--block" disabled>
            <span class="jbw-social-icon jbw-social-icon--apple">&#63743;</span> Continue with Apple
        </button>
    </div>

    <form method="POST" action="{{ route('web.guest') }}" class="jbw-form-stack jbw-form-stack--tight">@csrf
        <button type="submit" class="jbw-btn jbw-btn--ghost jbw-btn--block">Continue as a Guest</button>
    </form>

    <p class="jbw-auth-footer">{{ $footerPrompt }} <a href="{{ $footerHref }}">{{ $footerLink }}</a></p>
</x-web.auth-shell>

<script>
    (function () {
        const form = document.getElementById('mobile-auth-form');
        const input = document.getElementById('mobile');
        const hint = document.getElementById('mobile-hint');
        if (!form || !input) return;

        function digitsOnly(value) {
            let digits = value.replace(/\D/g, '');
            if (digits.length > 10 && digits.startsWith('91')) {
                digits = digits.slice(2);
            }
            return digits.slice(0, 10);
        }

        function formatMobile(value) {
            const digits = digitsOnly(value);
            return digits ? '+91 ' + digits : '';
        }

        function isValidIndianMobile(value) {
            const digits = digitsOnly(value);
            return /^[6-9]\d{9}$/.test(digits);
        }

        function showError(message) {
            input.classList.add('is-invalid');
            hint.textContent = message;
            hint.hidden = false;
        }

        function clearError() {
            input.classList.remove('is-invalid');
            hint.hidden = true;
            hint.textContent = '';
        }

        input.addEventListener('input', () => {
            const formatted = formatMobile(input.value);
            if (input.value !== formatted) {
                input.value = formatted;
            }
            if (isValidIndianMobile(input.value)) {
                clearError();
            }
        });

        input.addEventListener('blur', () => {
            if (input.value.trim() && !isValidIndianMobile(input.value)) {
                showError('Enter a valid 10-digit mobile number.');
            }
        });

        form.addEventListener('submit', (event) => {
            if (!isValidIndianMobile(input.value)) {
                event.preventDefault();
                showError('Enter a valid 10-digit mobile number.');
                input.focus();
            }
        });
    })();
</script>
