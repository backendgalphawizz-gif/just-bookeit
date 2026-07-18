<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FirebaseCloudMessagingService
{
    /** @return array<string, mixed>|null */
    protected function serviceAccount(): ?array
    {
        $path = $this->resolveServiceAccountPath();

        if (! $path || ! is_readable($path)) {
            Log::warning('Firebase service account file is missing or unreadable.', [
                'path' => $path ?? config('firebase.service_account_path'),
            ]);

            return null;
        }

        $json = json_decode((string) file_get_contents($path), true);

        return is_array($json) ? $json : null;
    }

    protected function resolveServiceAccountPath(): ?string
    {
        $path = config('firebase.service_account_path');

        if (! is_string($path) || trim($path) === '') {
            $fallback = config_path('firebase-service-account.json');

            return is_readable($fallback) ? $fallback : null;
        }

        $path = trim($path);

        // Absolute path (Windows drive / Unix root)
        if (preg_match('/^(?:[A-Za-z]:[\\\\\\/]|\\\\|\\/)/', $path) === 1) {
            return $path;
        }

        // Relative path from project root (e.g. config/firebase-service-account.json)
        $fromBase = base_path($path);
        if (is_readable($fromBase)) {
            return $fromBase;
        }

        // Also try as relative to config/
        $fromConfig = config_path(basename($path));
        if (is_readable($fromConfig)) {
            return $fromConfig;
        }

        return $fromBase;
    }

    protected function projectId(): ?string
    {
        $configured = config('firebase.project_id');

        if (filled($configured)) {
            return (string) $configured;
        }

        return $this->serviceAccount()['project_id'] ?? null;
    }

    public function getAccessToken(): ?string
    {
        return Cache::remember(
            (string) config('firebase.access_token_cache_key'),
            (int) config('firebase.access_token_ttl_seconds', 3500),
            fn () => $this->requestAccessToken()
        );
    }

    protected function requestAccessToken(): ?string
    {
        $account = $this->serviceAccount();

        if (! $account) {
            return null;
        }

        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $now = time();
        $payload = $this->base64UrlEncode(json_encode([
            'iss' => $account['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $signatureInput = $header.'.'.$payload;

        if (! openssl_sign($signatureInput, $signature, (string) $account['private_key'], OPENSSL_ALGO_SHA256)) {
            Log::error('Firebase JWT signing failed.');

            return null;
        }

        $jwt = $signatureInput.'.'.$this->base64UrlEncode($signature);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (! $response->successful()) {
            Log::error('Firebase access token request failed.', ['body' => $response->body()]);

            return null;
        }

        return $response->json('access_token');
    }

    /**
     * @param  array<string, mixed>  $message
     * @return array{success: bool, response: mixed, error: ?string}
     */
    public function sendToToken(string $token, array $message): array
    {
        $accessToken = $this->getAccessToken();
        $projectId = $this->projectId();

        if (! $accessToken || ! $projectId) {
            return [
                'success' => false,
                'response' => null,
                'error' => 'Firebase is not configured.',
            ];
        }

        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => (string) ($message['title'] ?? ''),
                    'body' => (string) ($message['body'] ?? ''),
                ],
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'sound' => 'default',
                    ],
                ],
                'data' => $this->stringifyDataPayload($message),
            ],
        ];

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $payload);

        if (! $response->successful()) {
            return [
                'success' => false,
                'response' => $response->json(),
                'error' => $response->body(),
            ];
        }

        return [
            'success' => true,
            'response' => $response->json(),
            'error' => null,
        ];
    }

    /**
     * @param  list<string>  $tokens
     * @param  array<string, mixed>  $message
     * @return array{sent: int, failed: int, responses: list<array<string, mixed>>}
     */
    public function sendToTokens(array $tokens, array $message): array
    {
        $tokens = array_values(array_filter(array_unique($tokens)));

        $sent = 0;
        $failed = 0;
        $responses = [];

        foreach ($tokens as $token) {
            $result = $this->sendToToken($token, $message);

            if ($result['success']) {
                $sent++;
            } else {
                $failed++;
            }

            $responses[] = $result;
        }

        return compact('sent', 'failed', 'responses');
    }

    /**
     * @param  array<string, mixed>  $message
     * @return array<string, string>
     */
    protected function stringifyDataPayload(array $message): array
    {
        $data = [
            'type' => (string) ($message['type'] ?? ''),
            'order_id' => (string) ($message['order_id'] ?? ''),
            'type_id' => (string) ($message['type_id'] ?? ''),
            'chat' => (string) ($message['chat'] ?? ''),
            'content_available' => 'true',
        ];

        foreach ($data as $key => $value) {
            $data[$key] = Str::limit($value, 1000, '');
        }

        return $data;
    }

    protected function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
