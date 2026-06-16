<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('web.partials.head')
</head>
<body class="jbw-body">
    @include('web.partials.header')
    <main class="jbw-main bannercss jbw-container" style="overflow-x:hidden">
        @if (session('success') || session('error') || session('info') || $errors->any())
            <div class="jbw-container jbw-flash-wrap">
                @include('web.partials.alert')
            </div>
        @endif
        @yield('content')
    </main>
    @include('web.partials.footer')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</body>
</html>
