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
        console.warn('[JustBook chat] Echo/Pusher not available');
        return;
    }

    // Same-origin path avoids APP_URL (127.0.0.1) vs LAN IP cookie/CORS mismatch.
    let authEndpoint = cfg.auth_endpoint || '/broadcasting/auth';
    try {
        if (/^https?:\/\//i.test(authEndpoint)) {
            authEndpoint = new URL(authEndpoint).pathname;
        }
    } catch (e) {
        authEndpoint = '/broadcasting/auth';
    }

    const wsHost = cfg.host || window.location.hostname;

    window.Echo = new EchoClass({
        broadcaster: 'reverb',
        key: cfg.key,
        wsHost: wsHost,
        wsPort: cfg.port,
        wssPort: cfg.port,
        forceTLS: !!cfg.useTLS,
        enabledTransports: ['ws', 'wss'],
        authEndpoint: authEndpoint,
        auth: {
            headers: {
                'X-CSRF-TOKEN': cfg.csrfToken,
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        },
        // Explicit cookie auth so vendor/customer session panels always authorize.
        authorizer: (channel) => ({
            authorize: (socketId, callback) => {
                const body = new URLSearchParams({
                    socket_id: socketId,
                    channel_name: channel.name,
                });

                fetch(authEndpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': cfg.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: body.toString(),
                })
                    .then(async (response) => {
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            throw data;
                        }
                        callback(null, data);
                    })
                    .catch((error) => {
                        console.warn('[JustBook chat] channel auth failed', channel.name, error);
                        callback(error, null);
                    });
            },
        }),
    });

    window.JustBookChatRealtime.ready = true;
    window.JustBookChatRealtime.wsHost = wsHost;
    window.JustBookChatRealtime.authEndpoint = authEndpoint;
})();
</script>
@endif
