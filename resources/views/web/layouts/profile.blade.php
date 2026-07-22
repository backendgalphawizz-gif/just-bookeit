<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('web.partials.head')
</head>
<body class="jbw-body">
    @include('web.partials.header')
    <main class="jbw-main jbw-main--profile">
        <div class="jbw-container">
            <div class="jbw-page-head">
                <h1 class="jbw-page-title">Profile</h1>
            </div>
            <div class="jbw-profile-shell">
                @include('web.partials.profile-sidebar')
                <div class="jbw-profile-content">
                    @yield('content')
                </div>
            </div>
        </div>
    </main>
    @include('web.partials.footer')
    @include('web.partials.toast')
    @include('web.partials.browse-flow-modals')
    <script src="/js/web-image-fallback.js"></script>
    <script defer src="/js/web-toast.js"></script>
    <script defer src="/js/web-location-detect.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</body>
</html>
