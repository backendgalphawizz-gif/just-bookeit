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

    public const TYPE_LOGIN = 'login';

    public const TYPE_REGISTER = 'register';

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

    public function send(string $actorType, string $mobile, string $type): array
    {
        $mobile = $this->normalizeMobile($mobile);
        $type = $this->normalizeType($type);
        $this->assertActorType($actorType);

        $this->assertAuthTypeMatchesAccount($actorType, $mobile, $type);

        $otp = (string) random_int(1000, 9999);

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

        $isRegistered = $this->isRegistered($actorType, $mobile);

        return [
            'mobile' => $mobile,
            'masked_mobile' => '+91 ******'.substr($mobile, -3),
            'expires_in_seconds' => (int) config('api.otp_ttl_minutes', 5) * 60,
            'type' => $type,
            'is_registered' => $isRegistered,
            'message' => $type === self::TYPE_LOGIN
                ? 'OTP sent for login.'
                : 'OTP sent for registration.',
            'otp' => $otp,
        ];
    }

    public function verify(string $actorType, string $mobile, string $otp, string $type): array
    {
        $mobile = $this->normalizeMobile($mobile);
        $type = $this->normalizeType($type);
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

        $this->assertAuthTypeMatchesAccount($actorType, $mobile, $type);

        if ($type === self::TYPE_LOGIN) {
            $actor = $this->findActor($actorType, $mobile);
            $this->assertActorCanAuthenticate($actorType, $actor);

            $token = $actor->createToken($this->tokenName($actorType))->plainTextToken;

            return [
                'type' => self::TYPE_LOGIN,
                'is_registered' => true,
                'requires_registration' => false,
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => $this->formatActor($actorType, $actor),
                'message' => 'Login successful.',
            ];
        }

        $registrationToken = Str::random(64);
        Cache::put($this->registrationCacheKey($actorType, $registrationToken), $mobile, now()->addMinutes(15));

        return [
            'type' => self::TYPE_REGISTER,
            'is_registered' => false,
            'requires_registration' => true,
            'registration_token' => $registrationToken,
            'masked_mobile' => '+91 ******'.substr($mobile, -3),
            'message' => 'OTP verified. Please complete registration.',
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
                'profile_image_url' => $actor->profileImageUrl(),
            ],
            self::ACTOR_VENDOR => [
                'id' => $actor->id,
                'type' => 'vendor',
                'vendor_code' => $actor->vendor_code,
                'shop_name' => $actor->shop_name ?? $actor->brand_name,
                'brand_name' => $actor->brand_name,
                'owner_name' => $actor->owner_name,
                'mobile' => $actor->mobile,
                'business_mobile' => $actor->business_mobile,
                'email' => $actor->email,
                'business_mail' => $actor->business_email,
                'service_types' => $actor->service_types ?? [],
                'gst_no' => $actor->gst_number,
                'address' => $actor->address,
                'country' => $actor->country,
                'state' => $actor->state,
                'city' => $actor->city,
                'pincode' => $actor->pincode,
                'account_name' => $actor->account_name,
                'account_no' => $actor->account_number,
                'ifsc_code' => $actor->ifsc_code,
                'bank_name' => $actor->bank_name,
                'account_type' => $actor->account_type,
                'status' => $actor->status,
                'profile_image_url' => $actor->profileImageUrl(),
                'shop_logo_url' => $actor->shopLogoUrl(),
                'pan_card_url' => $actor->panCardUrl(),
                'aadhar_front_url' => $actor->aadharFrontUrl(),
                'aadhar_back_url' => $actor->aadharBackUrl(),
            ],
            self::ACTOR_DRIVER => [
                'id' => $actor->id,
                'type' => 'driver',
                'driver_code' => $actor->driver_code,
                'name' => $actor->name,
                'mobile' => $actor->mobile,
                'email' => $actor->email,
                'city' => $actor->city,
                'vehicle_no' => $actor->vehicle_no,
                'status' => $actor->status,
                'is_verified' => $actor->is_verified,
                'profile_image_url' => $actor->profileImageUrl(),
                'aadhar_front_url' => $actor->aadharFrontUrl(),
                'aadhar_back_url' => $actor->aadharBackUrl(),
                'driving_licence_url' => $actor->drivingLicenceUrl(),
            ],
        };
    }

    protected function assertAuthTypeMatchesAccount(string $actorType, string $mobile, string $type): void
    {
        $isRegistered = $this->isRegistered($actorType, $mobile);

        if ($type === self::TYPE_REGISTER && $isRegistered) {
            throw ValidationException::withMessages([
                'type' => ['You are already registered. Please login first.'],
            ]);
        }

        if ($type === self::TYPE_LOGIN && ! $isRegistered) {
            throw ValidationException::withMessages([
                'type' => ['No account found with this mobile. Please register first.'],
            ]);
        }
    }

    protected function isRegistered(string $actorType, string $mobile): bool
    {
        $actor = $this->findActor($actorType, $mobile);

        return $actor && $this->isRegisteredActor($actorType, $actor);
    }

    protected function isRegisteredActor(string $actorType, Customer|Vendor|Driver $actor): bool
    {
        if ($actorType === self::ACTOR_CUSTOMER) {
            return ! ($actor->is_guest ?? false);
        }

        return true;
    }

    protected function assertActorCanAuthenticate(string $actorType, Customer|Vendor|Driver $actor): void
    {
        if ($actorType === self::ACTOR_VENDOR && $actor->status === 'rejected') {
            throw ValidationException::withMessages(['mobile' => ['This vendor account was rejected.']]);
        }

        if ($actorType === self::ACTOR_DRIVER && $actor->status === 'rejected') {
            throw ValidationException::withMessages(['mobile' => ['This driver account was rejected.']]);
        }

        if (in_array($actor->status ?? 'active', ['suspended', 'blocked'], true)) {
            throw ValidationException::withMessages(['mobile' => ['This account is suspended.']]);
        }
    }

    protected function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));

        if (! in_array($type, [self::TYPE_LOGIN, self::TYPE_REGISTER], true)) {
            throw ValidationException::withMessages([
                'type' => ['Type must be login or register.'],
            ]);
        }

        return $type;
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
