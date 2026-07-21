{{-- Shared Echo + Reverb bootstrap for customer & vendor chat --}}
@php
    $broadcast = \App\Support\BroadcastingConfig::clientConfig();
@endphp
@if ($broadcast['enabled'])
<script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0-rc2/dist/web/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
<script>
window.JustBookChatRealtime = @json($broadcast);
window.JustBookChatRealtime.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
window.JustBookChatRealtime.viewerId = @json($chatRealtimeViewerId ?? null);
window.JustBookChatRealtime.viewerRole = @json($chatRealtimeViewerRole ?? null);

(function () {
    const cfg = window.JustBookChatRealtime;
    const EchoClass = window.Echo;
    if (!cfg || !cfg.enabled || typeof EchoClass !== 'function' || !window.Pusher) {
        return;
    }

    window.Echo = new EchoClass({
        broadcaster: 'reverb',
        key: cfg.key,
        wsHost: cfg.host,
        wsPort: cfg.port,
        wssPort: cfg.port,
        forceTLS: !!cfg.useTLS,
        enabledTransports: ['ws', 'wss'],
        authEndpoint: cfg.auth_endpoint,
        auth: {
            headers: {
                'X-CSRF-TOKEN': cfg.csrfToken,
                Accept: 'application/json',
            },
        },
    });
})();
</script>
@endif
