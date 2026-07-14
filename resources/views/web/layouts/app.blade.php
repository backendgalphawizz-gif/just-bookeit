<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('web.partials.head')
</head>
<body @class(['jbw-body', 'jbw-body--chat' => request()->routeIs('web.chat.*')])>
    @include('web.partials.header')
    <main class="jbw-main">
        @yield('content')
    </main>
    @include('web.partials.footer')
    @include('web.partials.toast')
    <script defer src="/js/web-toast.js"></script>
    <script defer src="/js/chat-compose.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    @stack('scripts')
</body>
</html>
