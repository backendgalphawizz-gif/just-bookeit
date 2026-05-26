<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Api\ApiController;
use App\Models\Driver;
use App\Services\Auth\OtpService;
use App\Support\CodeGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverAuthController extends ApiController
{
    public function __construct(
        protected OtpService $otp
    ) {}

    public function sendOtp(Request $request): JsonResponse
    {
        $data = $request->validate(['mobile' => ['required', 'string', 'max:20']]);

        return $this->success($this->otp->send(OtpService::ACTOR_DRIVER, $data['mobile']), 'OTP sent.');
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'string', 'max:20'],
            'otp' => ['required', 'digits:4'],
        ]);

        return $this->success(
            $this->otp->verify(OtpService::ACTOR_DRIVER, $data['mobile'], $data['otp']),
            'OTP verified.'
        );
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'registration_token' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'aadhar' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ]);

        $mobile = $this->otp->consumeRegistrationToken(OtpService::ACTOR_DRIVER, $data['registration_token']);

        if (Driver::query()->where('mobile', $mobile)->exists()) {
            return $this->error('Driver already registered.', 409);
        }

        $aadharPath = $request->file('aadhar')->store('drivers/aadhar', 'public');

        $driver = Driver::query()->create([
            'driver_code' => CodeGenerator::driverCode(),
            'name' => $data['name'],
            'mobile' => $mobile,
            'email' => $data['email'] ?? null,
            'city' => $data['city'] ?? null,
            'aadhar_path' => $aadharPath,
            'status' => 'pending',
            'is_verified' => false,
            'registered_at' => now(),
        ]);

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

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->success(null, 'Logged out.');
    }
}
