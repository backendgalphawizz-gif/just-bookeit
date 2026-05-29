<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\ApiController;
use App\Models\Vendor;
use App\Services\Auth\OtpService;
use App\Support\CodeGenerator;
use App\Support\StoresUploadedFiles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorAuthController extends ApiController
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

        $payload = $this->otp->send(OtpService::ACTOR_VENDOR, $data['mobile'], $data['type']);

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
            OtpService::ACTOR_VENDOR,
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
            $this->vendorFieldRules(required: true)
        ));

        $mobile = $this->otp->consumeRegistrationToken(OtpService::ACTOR_VENDOR, $data['registration_token']);

        if (Vendor::query()->where('mobile', $mobile)->exists()) {
            return $this->error('Vendor already registered.', 409);
        }

        $vendor = new Vendor([
            ...$this->mapVendorAttributes($data),
            'vendor_code' => CodeGenerator::vendorCode(),
            'mobile' => $mobile,
            'status' => 'pending',
        ]);

        $this->applyVendorFiles($vendor, $request);
        $vendor->save();

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

    public function updateProfile(Request $request): JsonResponse
    {
        /** @var Vendor $vendor */
        $vendor = $request->user();

        $data = $request->validate(array_merge(
            $this->vendorFieldRules(required: false),
            ['profile_image' => ['sometimes', ...self::IMAGE_RULE]]
        ));

        $vendor->fill($this->mapVendorAttributes($data));
        $this->applyVendorFiles($vendor, $request);
        $vendor->save();

        return $this->success(
            $this->otp->formatActor(OtpService::ACTOR_VENDOR, $vendor->fresh()),
            'Profile updated.'
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->success(null, 'Logged out.');
    }

    private function vendorFieldRules(bool $required): array
    {
        $rule = $required ? 'required' : 'sometimes';

        return [
            'shop_name' => [$rule, 'string', 'max:255'],
            'owner_name' => [$rule, 'string', 'max:255'],
            'email' => [$rule, 'email', 'max:255'],
            'service_types' => [$rule, 'array', 'min:1'],
            'service_types.*' => ['integer', 'exists:categories,id'],
            'business_mobile' => [$rule, 'string', 'max:20'],
            'business_mail' => [$rule, 'email', 'max:255'],
            'gst_no' => ['nullable', 'string', 'max:15'],
            'address' => [$rule, 'string', 'max:500'],
            'country' => [$rule, 'string', 'max:100'],
            'state' => [$rule, 'string', 'max:100'],
            'city' => [$rule, 'string', 'max:100'],
            'pincode' => [$rule, 'string', 'max:10'],
            'aadhar_front' => [$rule, ...self::IMAGE_RULE],
            'aadhar_back' => [$rule, ...self::IMAGE_RULE],
            'shop_logo' => [$rule, ...self::IMAGE_RULE],
            'pan_card' => [$rule, ...self::IMAGE_RULE],
            'account_name' => [$rule, 'string', 'max:255'],
            'account_no' => [$rule, 'string', 'max:20'],
            'ifsc_code' => [$rule, 'string', 'max:11'],
            'bank_name' => [$rule, 'string', 'max:255'],
            'account_type' => [$rule, 'in:savings,current'],
        ];
    }

    private function mapVendorAttributes(array $data): array
    {
        $attributes = collect($data)->only([
            'shop_name',
            'owner_name',
            'email',
            'service_types',
            'business_mobile',
            'address',
            'country',
            'state',
            'city',
            'pincode',
            'account_name',
            'ifsc_code',
            'bank_name',
            'account_type',
        ])->all();

        if (isset($data['shop_name'])) {
            $attributes['brand_name'] = $data['shop_name'];
        }

        if (isset($data['business_mail'])) {
            $attributes['business_email'] = $data['business_mail'];
        }

        if (array_key_exists('gst_no', $data)) {
            $attributes['gst_number'] = $data['gst_no'];
        }

        if (isset($data['account_no'])) {
            $attributes['account_number'] = $data['account_no'];
        }

        return $attributes;
    }

    private function applyVendorFiles(Vendor $vendor, Request $request): void
    {
        $files = [
            'profile_image' => ['column' => 'profile_image_path', 'dir' => 'vendors/profile-images'],
            'aadhar_front' => ['column' => 'aadhar_front_path', 'dir' => 'vendors/aadhar/front'],
            'aadhar_back' => ['column' => 'aadhar_back_path', 'dir' => 'vendors/aadhar/back'],
            'shop_logo' => ['column' => 'shop_logo_path', 'dir' => 'vendors/shop-logos'],
            'pan_card' => ['column' => 'pan_card_path', 'dir' => 'vendors/pan-cards'],
        ];

        foreach ($files as $input => $config) {
            if (! $request->hasFile($input)) {
                continue;
            }

            $vendor->{$config['column']} = StoresUploadedFiles::replace(
                $request->file($input),
                $vendor->{$config['column']},
                $config['dir']
            );
        }
    }
}
