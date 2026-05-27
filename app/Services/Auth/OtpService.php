<?php

namespace App\Services\Auth;

use App\Models\Customer;
use App\Models\Driver;
use App\Models\OtpVerification;
use App\Models\Vendor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public const ACTOR_CUSTOMER = 'customer';

    public const ACTOR_VENDOR = 'vendor';

    public const ACTOR_DRIVER = 'driver';

    public function normalizeMobile(string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', $mobile) ?? '';

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) !== 10) {
            throw ValidationException::withMessages([
                'mobile' => ['Enter a valid 10-digit mobile number.'],
            ]);
        }

        return $digits;
    }

    public function send(string $actorType, string $mobile): array
    {
        $mobile = $this->normalizeMobile($mobile);
        $this->assertActorType($actorType);

        $testMode = (bool) config('api.otp_test_mode', false);

        $otp = $testMode
            ? (string) config('api.otp_debug_code', '1234')
            : (string) random_int(1000, 9999);

        OtpVerification::query()
            ->where('actor_type', $actorType)
            ->where('mobile', $mobile)
            ->delete();

        OtpVerification::query()->create([
            'actor_type' => $actorType,
            'mobile' => $mobile,
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes((int) config('api.otp_ttl_minutes', 5)),
        ]);

        $payload = [
            'mobile' => $mobile,
            'masked_mobile' => '+91 ******'.substr($mobile, -3),
            'expires_in_seconds' => (int) config('api.otp_ttl_minutes', 5) * 60,
            'message' => 'OTP sent successfully.',
        ];

        if ($testMode) {
            $payload['otp'] = $otp;
            $payload['debug_otp'] = $otp;
            $payload['test_mode'] = true;
        }

        return $payload;
    }

    public function verify(string $actorType, string $mobile, string $otp): array
    {
        $mobile = $this->normalizeMobile($mobile);
        $this->assertActorType($actorType);

        $record = OtpVerification::query()
            ->where('actor_type', $actorType)
            ->where('mobile', $mobile)
            ->latest('id')
            ->first();

        if (! $record || $record->expires_at->isPast()) {
            throw ValidationException::withMessages(['otp' => ['OTP expired. Request a new code.']]);
        }

        if ($record->attempts >= 5) {
            throw ValidationException::withMessages(['otp' => ['Too many attempts. Request a new OTP.']]);
        }

        if (! Hash::check($otp, $record->otp_hash)) {
            $record->increment('attempts');
            throw ValidationException::withMessages(['otp' => ['Invalid OTP.']]);
        }

        $record->update(['verified_at' => now()]);

        $registrationToken = Str::random(64);
        Cache::put($this->registrationCacheKey($actorType, $registrationToken), $mobile, now()->addMinutes(15));

        $actor = $this->findActor($actorType, $mobile);

        if ($actor) {
            if ($actorType === self::ACTOR_VENDOR && $actor->status === 'rejected') {
                throw ValidationException::withMessages(['mobile' => ['This vendor account was rejected.']]);
            }

            if ($actorType === self::ACTOR_DRIVER && $actor->status === 'rejected') {
                throw ValidationException::withMessages(['mobile' => ['This driver account was rejected.']]);
            }

            if (in_array($actor->status ?? 'active', ['suspended', 'blocked'], true)) {
                throw ValidationException::withMessages(['mobile' => ['This account is suspended.']]);
            }

            $token = $actor->createToken($this->tokenName($actorType))->plainTextToken;

            return [
                'requires_registration' => false,
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => $this->formatActor($actorType, $actor),
            ];
        }

        return [
            'requires_registration' => true,
            'registration_token' => $registrationToken,
            'masked_mobile' => '+91 ******'.substr($mobile, -3),
        ];
    }

    public function consumeRegistrationToken(string $actorType, string $registrationToken): string
    {
        $mobile = Cache::pull($this->registrationCacheKey($actorType, $registrationToken));

        if (! $mobile) {
            throw ValidationException::withMessages([
                'registration_token' => ['Registration session expired. Verify OTP again.'],
            ]);
        }

        return $mobile;
    }

    public function findActor(string $actorType, string $mobile): Customer|Vendor|Driver|null
    {
        return match ($actorType) {
            self::ACTOR_CUSTOMER => Customer::query()->where('mobile', $mobile)->first(),
            self::ACTOR_VENDOR => Vendor::query()->where('mobile', $mobile)->first(),
            self::ACTOR_DRIVER => Driver::query()->where('mobile', $mobile)->first(),
        };
    }

    public function formatActor(string $actorType, Customer|Vendor|Driver $actor): array
    {
        return match ($actorType) {
            self::ACTOR_CUSTOMER => [
                'id' => $actor->id,
                'type' => 'customer',
                'customer_code' => $actor->customer_code,
                'name' => $actor->name,
                'mobile' => $actor->mobile,
                'email' => $actor->email,
                'city' => $actor->city,
                'status' => $actor->status,
                'is_verified' => $actor->is_verified,
                'is_guest' => $actor->is_guest,
            ],
            self::ACTOR_VENDOR => [
                'id' => $actor->id,
                'type' => 'vendor',
                'vendor_code' => $actor->vendor_code,
                'brand_name' => $actor->brand_name,
                'owner_name' => $actor->owner_name,
                'mobile' => $actor->mobile,
                'email' => $actor->email,
                'city' => $actor->city,
                'status' => $actor->status,
            ],
            self::ACTOR_DRIVER => [
                'id' => $actor->id,
                'type' => 'driver',
                'driver_code' => $actor->driver_code,
                'name' => $actor->name,
                'mobile' => $actor->mobile,
                'email' => $actor->email,
                'city' => $actor->city,
                'status' => $actor->status,
                'is_verified' => $actor->is_verified,
                'aadhar_url' => $actor->aadharUrl(),
            ],
        };
    }

    protected function registrationCacheKey(string $actorType, string $token): string
    {
        return "api:register:{$actorType}:{$token}";
    }

    protected function tokenName(string $actorType): string
    {
        return match ($actorType) {
            self::ACTOR_CUSTOMER => 'customer-api',
            self::ACTOR_VENDOR => 'vendor-api',
            self::ACTOR_DRIVER => 'driver-api',
        };
    }

    protected function assertActorType(string $actorType): void
    {
        if (! in_array($actorType, [self::ACTOR_CUSTOMER, self::ACTOR_VENDOR, self::ACTOR_DRIVER], true)) {
            throw ValidationException::withMessages(['actor' => ['Invalid actor type.']]);
        }
    }
}
