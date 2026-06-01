<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('web.partials.head')
</head>
<body class="jbw-body jbw-body--guest">
    <main class="jbw-auth-main">
        @php
            $devOtp = session('info') && str_starts_with((string) session('info'), 'Dev OTP:')
                ? session('info')
                : null;
        @endphp

        @if ($devOtp)
            <div class="jbw-dev-otp-badge" role="status">{{ $devOtp }}</div>
        @endif

        @if (session('success') || session('error') || ($errors->any()) || (session('info') && ! $devOtp))
            <div class="jbw-auth-flash">
                @include('web.partials.alert', ['skipInfo' => (bool) $devOtp])
            </div>
        @endif
        @yield('content')
    </main>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</body>
</html>
