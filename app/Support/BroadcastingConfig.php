<?php

namespace App\Support;

class BroadcastingConfig
{
    /** @return array<string, mixed> */
    public static function clientConfig(?string $authEndpoint = null): array
    {
        $enabled = config('broadcasting.default') === 'reverb'
            && filled(config('broadcasting.connections.reverb.key'));

        $clientHost = config('broadcasting.connections.reverb.client_host');
        if (! filled($clientHost)) {
            $clientHost = request()?->getHost() ?: (string) (config('broadcasting.connections.reverb.options.host') ?: '127.0.0.1');
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
            'auth_endpoint' => $authEndpoint ?? url('/broadcasting/auth'),
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
}
