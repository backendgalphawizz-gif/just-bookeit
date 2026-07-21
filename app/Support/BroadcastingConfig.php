<?php

namespace App\Support;

class BroadcastingConfig
{
    /**
     * Client config for web Echo and mobile Pusher clients.
     * Host + auth endpoint always follow the current request host so LAN IPs work
     * even when APP_URL is http://127.0.0.1:8000.
     *
     * @param  string|null  $authEndpoint  Absolute URL, or path like /api/v1/broadcasting/auth
     * @return array<string, mixed>
     */
    public static function clientConfig(?string $authEndpoint = null): array
    {
        $enabled = config('broadcasting.default') === 'reverb'
            && filled(config('broadcasting.connections.reverb.key'));

        $request = request();
        $requestRoot = $request
            ? $request->getSchemeAndHttpHost()
            : rtrim((string) config('app.url'), '/');

        $clientHost = config('broadcasting.connections.reverb.client_host');
        if (! filled($clientHost)) {
            $clientHost = $request?->getHost()
                ?: (string) (config('broadcasting.connections.reverb.options.host') ?: '127.0.0.1');
        }

        $port = (int) (config('broadcasting.connections.reverb.options.port') ?: 8080);
        $scheme = (string) (config('broadcasting.connections.reverb.options.scheme') ?: 'http');

        return [
            'enabled' => $enabled,
            'driver' => 'reverb',
            'key' => (string) config('broadcasting.connections.reverb.key'),
            'host' => (string) $clientHost,
            'port' => $port,
            'scheme' => $scheme,
            'useTLS' => $scheme === 'https',
            'auth_endpoint' => self::resolveAuthEndpoint($authEndpoint, $requestRoot),
            'channels' => [
                'conversation' => 'private-chat.conversation.{id}',
                'customer_inbox' => 'private-chat.customer.{id}',
                'vendor_inbox' => 'private-chat.vendor.{id}',
            ],
            'events' => [
                'created' => '.chat.message.created',
                'updated' => '.chat.message.updated',
                'deleted' => '.chat.message.deleted',
            ],
        ];
    }

    protected static function resolveAuthEndpoint(?string $authEndpoint, string $requestRoot): string
    {
        if (! filled($authEndpoint)) {
            return $requestRoot.'/broadcasting/auth';
        }

        if (str_starts_with($authEndpoint, 'http://') || str_starts_with($authEndpoint, 'https://')) {
            $path = parse_url($authEndpoint, PHP_URL_PATH) ?: '/broadcasting/auth';
            $query = parse_url($authEndpoint, PHP_URL_QUERY);

            return $requestRoot.$path.($query ? '?'.$query : '');
        }

        return $requestRoot.'/'.ltrim($authEndpoint, '/');
    }
}
