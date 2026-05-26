<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\ApiController;
use App\Models\Vendor;
use App\Services\Auth\OtpService;
use App\Support\CodeGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorAuthController extends ApiController
{
    public function __construct(
        protected OtpService $otp
    ) {}

    public function sendOtp(Request $request): JsonResponse
    {
        $data = $request->validate(['mobile' => ['required', 'string', 'max:20']]);

        return $this->success($this->otp->send(OtpService::ACTOR_VENDOR, $data['mobile']), 'OTP sent.');
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'string', 'max:20'],
            'otp' => ['required', 'digits:4'],
        ]);

        return $this->success(
            $this->otp->verify(OtpService::ACTOR_VENDOR, $data['mobile'], $data['otp']),
            'OTP verified.'
        );
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'registration_token' => ['required', 'string'],
            'brand_name' => ['required', 'string', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
        ]);

        $mobile = $this->otp->consumeRegistrationToken(OtpService::ACTOR_VENDOR, $data['registration_token']);

        if (Vendor::query()->where('mobile', $mobile)->exists()) {
            return $this->error('Vendor already registered.', 409);
        }

        $vendor = Vendor::query()->create([
            'vendor_code' => CodeGenerator::vendorCode(),
            'brand_name' => $data['brand_name'],
            'owner_name' => $data['owner_name'],
            'mobile' => $mobile,
            'email' => $data['email'],
            'city' => $data['city'] ?? null,
            'status' => 'pending',
        ]);

        $token = $vendor->createToken('vendor-api')->plainTextToken;

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->otp->formatActor(OtpService::ACTOR_VENDOR, $vendor),
        ], 'Vendor registration submitted for approval.', 201);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var Vendor $vendor */
        $vendor = $request->user();

        return $this->success($this->otp->formatActor(OtpService::ACTOR_VENDOR, $vendor));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->success(null, 'Logged out.');
    }
}
