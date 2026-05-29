<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Api\ApiController;
use App\Models\Driver;
use App\Services\Auth\OtpService;
use App\Support\CodeGenerator;
use App\Support\StoresUploadedFiles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverAuthController extends ApiController
{
    private const IMAGE_RULE = ['image', 'mimes:jpeg,jpg,png,webp', 'max:4096'];

    public function __construct(
        protected OtpService $otp
    ) {}

    public function sendOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'string', 'max:20'],
            'type' => ['required', 'in:login,register'],
        ]);

        $payload = $this->otp->send(OtpService::ACTOR_DRIVER, $data['mobile'], $data['type']);

        return $this->success($payload, $payload['message']);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'string', 'max:20'],
            'otp' => ['required', 'digits:4'],
            'type' => ['required', 'in:login,register'],
        ]);

        $payload = $this->otp->verify(
            OtpService::ACTOR_DRIVER,
            $data['mobile'],
            $data['otp'],
            $data['type']
        );

        return $this->success($payload, $payload['message']);
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate(array_merge(
            ['registration_token' => ['required', 'string']],
            $this->driverFieldRules(required: true)
        ));

        $mobile = $this->otp->consumeRegistrationToken(OtpService::ACTOR_DRIVER, $data['registration_token']);

        if (Driver::query()->where('mobile', $mobile)->exists()) {
            return $this->error('Driver already registered.', 409);
        }

        $driver = new Driver([
            ...$this->mapDriverAttributes($data),
            'driver_code' => CodeGenerator::driverCode(),
            'mobile' => $mobile,
            'status' => 'pending',
            'is_verified' => false,
            'registered_at' => now(),
        ]);

        $this->applyDriverFiles($driver, $request);
        $driver->save();

        $token = $driver->createToken('driver-api')->plainTextToken;

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->otp->formatActor(OtpService::ACTOR_DRIVER, $driver),
        ], 'Driver registration submitted for approval.', 201);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var Driver $driver */
        $driver = $request->user();

        return $this->success($this->otp->formatActor(OtpService::ACTOR_DRIVER, $driver));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        /** @var Driver $driver */
        $driver = $request->user();

        $data = $request->validate(array_merge(
            $this->driverFieldRules(required: false),
            $this->driverBankFieldRules(),
            ['profile_image' => ['sometimes', ...self::IMAGE_RULE]]
        ));

        $driver->fill($this->mapDriverAttributes($data));
        $this->applyDriverFiles($driver, $request);
        $driver->save();

        return $this->success([
            'user' => $this->otp->formatActor(OtpService::ACTOR_DRIVER, $driver->fresh()),
        ], 'Profile updated.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->success(null, 'Logged out.');
    }

    private function driverFieldRules(bool $required): array
    {
        $rule = $required ? 'required' : 'sometimes';
        $emailRule = $required ? 'nullable' : 'sometimes';

        return [
            'name' => [$rule, 'string', 'max:255'],
            'email' => [$emailRule, 'nullable', 'email', 'max:255'],
            'city' => [$emailRule, 'nullable', 'string', 'max:100'],
            'vehicle_no' => [$required ? 'nullable' : 'sometimes', 'nullable', 'string', 'max:20'],
            'aadhar_front' => [$rule, ...self::IMAGE_RULE],
            'aadhar_back' => [$rule, ...self::IMAGE_RULE],
            'driving_licence' => [$rule, ...self::IMAGE_RULE],
        ];
    }

    private function mapDriverAttributes(array $data): array
    {
        $attributes = collect($data)->only([
            'name',
            'email',
            'city',
            'vehicle_no',
            'account_name',
            'ifsc_code',
            'bank_name',
            'account_type',
        ])->all();

        if (isset($data['account_no'])) {
            $attributes['account_number'] = $data['account_no'];
        }

        return $attributes;
    }

    private function driverBankFieldRules(): array
    {
        return [
            'account_name' => ['sometimes', 'string', 'max:255'],
            'account_no' => ['sometimes', 'string', 'max:20'],
            'ifsc_code' => ['sometimes', 'string', 'max:11'],
            'bank_name' => ['sometimes', 'string', 'max:255'],
            'account_type' => ['sometimes', 'in:savings,current'],
        ];
    }

    private function applyDriverFiles(Driver $driver, Request $request): void
    {
        $files = [
            'profile_image' => ['column' => 'profile_image_path', 'dir' => 'drivers/profile-images'],
            'aadhar_front' => ['column' => 'aadhar_front_path', 'dir' => 'drivers/aadhar/front'],
            'aadhar_back' => ['column' => 'aadhar_back_path', 'dir' => 'drivers/aadhar/back'],
            'driving_licence' => ['column' => 'driving_licence_path', 'dir' => 'drivers/driving-licence'],
        ];

        foreach ($files as $input => $config) {
            if (! $request->hasFile($input)) {
                continue;
            }

            $driver->{$config['column']} = StoresUploadedFiles::replace(
                $request->file($input),
                $driver->{$config['column']},
                $config['dir']
            );
        }
    }
}
