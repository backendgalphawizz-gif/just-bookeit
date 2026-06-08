<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Vendor Partner') — Just Book IT</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @include('vendor.partials.styles')
    @include('partials.panel-lightbox-assets')
</head>
<body class="vp-body">
    <div class="vp-guest-wrap">
        @yield('content')
    </div>

    @include('vendor.partials.alert')
    @include('vendor.partials.global-confirm')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <script defer src="{{ asset('js/vendor-panel.js') }}"></script>
</body>
</html>
