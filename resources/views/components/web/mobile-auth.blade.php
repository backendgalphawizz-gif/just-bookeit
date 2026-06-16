@props(['mode' => 'login'])

@php
    $isLogin = $mode === 'login';
    $title = $isLogin ? 'Sign in to your Account' : 'Sign up to your Account';
    $footerPrompt = $isLogin ? "Don't have an account?" : 'Already have an account?';
    $footerHref = $isLogin ? route('web.register') : route('web.login');
    $footerLink = $isLogin ? 'Register Now' : 'Sign in';
    $otpType = $isLogin ? 'login' : 'register';

    $mobileDigits = preg_replace('/\D+/', '', (string) old('mobile', ''));
    if (strlen($mobileDigits) === 12 && str_starts_with($mobileDigits, '91')) {
        $mobileDigits = substr($mobileDigits, 2);
    }
    $mobileDigits = substr($mobileDigits, 0, 10);
@endphp

<x-web.auth-shell :title="$title" subtitle="Enter your mobile number for verification">
    <form method="POST" action="{{ route('web.login.otp') }}" class="jbw-form-stack" id="mobile-auth-form" novalidate>
        @csrf
        <input type="hidden" name="type" value="{{ $otpType }}">

        <div class="jbw-field">
            <label class="jbw-label" for="mobile">Mobile number</label>
            <div class="jbw-phone-field @error('mobile') is-invalid @enderror">
                <span class="jbw-phone-prefix" aria-hidden="true">+91</span>
                <input
                    id="mobile"
                    type="tel"
                    name="mobile"
                    class="jbw-phone-input @error('mobile') is-invalid @enderror"
                    value="{{ $mobileDigits }}"
                    placeholder="9876543210"
                    inputmode="numeric"
                    autocomplete="tel-national"
                    maxlength="10"
                    pattern="[6-9][0-9]{9}"
                    required
                    autofocus
                >
            </div>
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
        const field = input?.closest('.jbw-phone-field');
        if (!form || !input) return;

        function digitsOnly(value) {
            return value.replace(/\D/g, '').slice(0, 10);
        }

        function isValidIndianMobile(value) {
            return /^[6-9]\d{9}$/.test(digitsOnly(value));
        }

        function showError(message) {
            input.classList.add('is-invalid');
            field?.classList.add('is-invalid');
            hint.textContent = message;
            hint.hidden = false;
        }

        function clearError() {
            input.classList.remove('is-invalid');
            field?.classList.remove('is-invalid');
            hint.hidden = true;
            hint.textContent = '';
        }

        input.addEventListener('input', () => {
            const digits = digitsOnly(input.value);
            if (input.value !== digits) {
                input.value = digits;
            }
            if (isValidIndianMobile(digits)) {
                clearError();
            }
        });

        input.addEventListener('blur', () => {
            if (input.value.trim() && !isValidIndianMobile(input.value)) {
                showError('Enter a valid 10-digit mobile number starting with 6–9.');
            }
        });

        form.addEventListener('submit', (event) => {
            input.value = digitsOnly(input.value);

            if (!isValidIndianMobile(input.value)) {
                event.preventDefault();
                showError('Enter a valid 10-digit mobile number starting with 6–9.');
                input.focus();
            }
        });
    })();
</script>
