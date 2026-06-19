<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Driver;
use App\Models\NotificationLog;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;

class PushNotificationService
{
    public function __construct(
        protected FirebaseCloudMessagingService $fcm
    ) {}

    /**
     * @param  array<string, mixed>  $extra  type, order_id, type_id, chat
     * @return array{sent: int, failed: int, tokens: int}
     */
    public function sendToAudience(string $audience, string $title, string $body, array $extra = []): array
    {
        $tokens = $this->tokensForAudience($audience);

        if ($tokens === []) {
            return ['sent' => 0, 'failed' => 0, 'tokens' => 0];
        }

        $result = $this->fcm->sendToTokens($tokens, [
            'title' => $title,
            'body' => $body,
            ...$extra,
        ]);

        return [
            'sent' => $result['sent'],
            'failed' => $result['failed'],
            'tokens' => count($tokens),
        ];
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public function sendToCustomer(Customer $customer, string $title, string $body, array $extra = []): bool
    {
        if (! filled($customer->fcm_token)) {
            return false;
        }

        return $this->fcm->sendToToken($customer->fcm_token, [
            'title' => $title,
            'body' => $body,
            ...$extra,
        ])['success'];
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public function sendToVendor(Vendor $vendor, string $title, string $body, array $extra = []): bool
    {
        if (! filled($vendor->fcm_token)) {
            return false;
        }

        return $this->fcm->sendToToken($vendor->fcm_token, [
            'title' => $title,
            'body' => $body,
            ...$extra,
        ])['success'];
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public function sendToDriver(Driver $driver, string $title, string $body, array $extra = []): bool
    {
        if (! filled($driver->fcm_token)) {
            return false;
        }

        return $this->fcm->sendToToken($driver->fcm_token, [
            'title' => $title,
            'body' => $body,
            ...$extra,
        ])['success'];
    }

    public function dispatchNotificationLog(NotificationLog $notification): array
    {
        if ($notification->channel !== 'push') {
            return ['sent' => 0, 'failed' => 0, 'tokens' => 0];
        }

        return $this->sendToAudience(
            $notification->audience,
            $notification->title,
            $notification->message,
            ['type' => 'announcement', 'type_id' => (string) $notification->id]
        );
    }

    /** @return list<string> */
    protected function tokensForAudience(string $audience): array
    {
        return match ($audience) {
            'all_customers', 'customers' => $this->pluckTokens(
                Customer::query()
                    ->where('status', 'active')
                    ->where('is_guest', false)
            ),
            'all_vendors', 'vendors' => $this->pluckTokens(
                Vendor::query()->where('status', 'active')
            ),
            'all_drivers', 'drivers' => $this->pluckTokens(
                Driver::query()->where('status', 'active')
            ),
            default => [],
        };
    }

    /** @return list<string> */
    protected function pluckTokens(Builder $query): array
    {
        return $query
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->pluck('fcm_token')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
