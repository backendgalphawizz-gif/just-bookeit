<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Customer;
use App\Services\Auth\OtpService;
use App\Support\CodeGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserAuthController extends ApiController
{
    public function __construct(
        protected OtpService $otp
    ) {}

    public function sendOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'string', 'max:20'],
        ]);

        return $this->success(
            $this->otp->send(OtpService::ACTOR_CUSTOMER, $data['mobile']),
            'OTP sent.'
        );
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'string', 'max:20'],
            'otp' => ['required', 'digits:4'],
        ]);

        return $this->success(
            $this->otp->verify(OtpService::ACTOR_CUSTOMER, $data['mobile'], $data['otp']),
            'OTP verified.'
        );
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'registration_token' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $mobile = $this->otp->consumeRegistrationToken(OtpService::ACTOR_CUSTOMER, $data['registration_token']);

        if (Customer::query()->where('mobile', $mobile)->exists()) {
            return $this->error('Account already exists. Please login.', 409);
        }

        $customer = Customer::query()->create([
            'customer_code' => CodeGenerator::customerCode(),
            'name' => $data['name'],
            'mobile' => $mobile,
            'email' => $data['email'] ?? null,
            'status' => 'active',
            'is_verified' => true,
            'is_guest' => false,
            'registered_at' => now(),
        ]);

        $token = $customer->createToken('customer-api')->plainTextToken;

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->otp->formatActor(OtpService::ACTOR_CUSTOMER, $customer),
        ], 'Registration successful.', 201);
    }

    public function guest(Request $request): JsonResponse
    {
        $customer = Customer::query()->create([
            'customer_code' => CodeGenerator::customerCode(),
            'name' => 'Guest',
            'mobile' => '9'.now()->format('mdHis').random_int(10, 99),
            'status' => 'active',
            'is_verified' => false,
            'is_guest' => true,
            'registered_at' => now(),
        ]);

        $token = $customer->createToken('customer-api-guest')->plainTextToken;

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->otp->formatActor(OtpService::ACTOR_CUSTOMER, $customer),
        ], 'Guest session created.', 201);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        return $this->success($this->otp->formatActor(OtpService::ACTOR_CUSTOMER, $customer));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->success(null, 'Logged out.');
    }
}
