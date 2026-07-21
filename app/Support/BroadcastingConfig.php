<?php

namespace App\Support;

class BroadcastingConfig
{
    /**
     * Client config for web Echo and mobile Pusher clients.
     *
     * Server-side PHP → Reverb uses REVERB_HOST / REVERB_PORT / REVERB_SCHEME
     * (usually 127.0.0.1:8080 http). Clients use REVERB_CLIENT_* when set,
     * otherwise the current request host + server Reverb port/scheme.
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

        $serverHost = (string) (config('broadcasting.connections.reverb.options.host') ?: '127.0.0.1');
        $serverPort = (int) (config('broadcasting.connections.reverb.options.port') ?: 8080);
        $serverScheme = (string) (config('broadcasting.connections.reverb.options.scheme') ?: 'http');

        $clientHost = config('broadcasting.connections.reverb.client_host');
        if (! filled($clientHost)) {
            $clientHost = $request?->getHost() ?: $serverHost;
        }

        $clientPort = config('broadcasting.connections.reverb.client_port');
        $clientPort = filled($clientPort) ? (int) $clientPort : $serverPort;

        $clientScheme = config('broadcasting.connections.reverb.client_scheme');
        $clientScheme = filled($clientScheme) ? (string) $clientScheme : $serverScheme;

        return [
            'enabled' => $enabled,
            'driver' => 'reverb',
            'key' => (string) config('broadcasting.connections.reverb.key'),
            'host' => (string) $clientHost,
            'port' => $clientPort,
            'scheme' => $clientScheme,
            'useTLS' => $clientScheme === 'https',
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
