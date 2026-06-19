<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\Driver;
use App\Models\Vendor;
use App\Services\Auth\OtpService;
use Illuminate\Support\Facades\Cache;

class StoresActorFcmToken
{
    /** @return array<string, list<string>> */
    public static function validationRules(): array
    {
        return [
            'fcm_token' => ['nullable', 'string', 'max:500'],
        ];
    }

    public static function rememberPending(string $actorType, string $mobile, ?string $token): void
    {
        if (! filled($token)) {
            return;
        }

        Cache::put(self::cacheKey($actorType, $mobile), $token, now()->addMinutes(20));
    }

    public static function pullPending(string $actorType, string $mobile): ?string
    {
        $token = Cache::pull(self::cacheKey($actorType, $mobile));

        return filled($token) ? (string) $token : null;
    }

    public static function saveForMobile(string $actorType, string $mobile, ?string $token): void
    {
        if (! filled($token)) {
            return;
        }

        $actor = match ($actorType) {
            OtpService::ACTOR_CUSTOMER => Customer::query()->where('mobile', $mobile)->first(),
            OtpService::ACTOR_VENDOR => Vendor::query()->where('mobile', $mobile)->first(),
            OtpService::ACTOR_DRIVER => Driver::query()->where('mobile', $mobile)->first(),
            default => null,
        };

        if ($actor) {
            $actor->update(['fcm_token' => $token]);
        }
    }

    public static function saveForActor(Customer|Vendor|Driver $actor, ?string $token): void
    {
        if (filled($token)) {
            $actor->update(['fcm_token' => $token]);
        }
    }

    protected static function cacheKey(string $actorType, string $mobile): string
    {
        return "api:fcm_pending:{$actorType}:{$mobile}";
    }
}
