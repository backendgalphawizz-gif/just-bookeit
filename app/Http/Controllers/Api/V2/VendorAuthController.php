<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\ApiController;
use App\Models\Vendor;
use App\Services\Auth\OtpService;
use App\Support\AdminValidationRules;
use App\Support\CodeGenerator;
use App\Support\LocationResolver;
use App\Support\StoresActorFcmToken;
use App\Support\StoresUploadedFiles;
use App\Support\VendorValidationRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VendorAuthController extends ApiController
{
    private const IMAGE_RULE = ['image', 'mimes:jpeg,jpg,png,webp', 'max:'.VendorValidationRules::MAX_IMAGE_KB];

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
        $data = $request->validate(array_merge([
            'mobile' => ['required', 'string', 'max:20'],
            'otp' => ['required', 'digits:4'],
            'type' => ['required', 'in:login,register'],
        ], StoresActorFcmToken::validationRules()));

        $payload = $this->otp->verify(
            OtpService::ACTOR_VENDOR,
            $data['mobile'],
            $data['otp'],
            $data['type']
        );

        $mobile = $this->otp->normalizeMobile($data['mobile']);

        if ($data['type'] === 'login') {
            StoresActorFcmToken::saveForMobile(OtpService::ACTOR_VENDOR, $mobile, $data['fcm_token'] ?? null);
        } else {
            StoresActorFcmToken::rememberPending(OtpService::ACTOR_VENDOR, $mobile, $data['fcm_token'] ?? null);
        }

        return $this->success($payload, $payload['message']);
    }

    public function register(Request $request): JsonResponse
    {
        $this->prepareRegisterInput($request);

        $data = $request->validate(
            array_merge(
                ['registration_token' => ['required', 'string']],
                StoresActorFcmToken::validationRules(),
                VendorValidationRules::apiRegister()
            ),
            VendorValidationRules::messages(),
            VendorValidationRules::attributes()
        );

        $mobile = $this->otp->consumeRegistrationToken(OtpService::ACTOR_VENDOR, $data['registration_token']);
        $fcmToken = $data['fcm_token'] ?? StoresActorFcmToken::pullPending(OtpService::ACTOR_VENDOR, $mobile);

        if (Vendor::query()->where('mobile', $mobile)->exists()) {
            return $this->error('Vendor already registered.', 409);
        }

        $shopName = $data['shop_name'] ?? $data['brand_name'];

        $vendor = Vendor::query()->create([
            'vendor_code' => CodeGenerator::vendorCode(),
            'shop_name' => $shopName,
            'brand_name' => $shopName,
            'owner_name' => $data['owner_name'],
            'email' => $data['email'],
            'mobile' => $mobile,
            'business_mobile' => $data['business_mobile'] ?? null,
            'business_email' => $data['business_mail'] ?? null,
            'aadhar_number' => $data['aadhar_number'] ?? null,
            'gst_number' => isset($data['gst_no']) ? strtoupper($data['gst_no']) : null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'country' => $data['country'] ?? null,
            'pincode' => $data['pincode'] ?? null,
            'service_types' => implode(', ', VendorValidationRules::normalizeServiceTypes($data['service_types'] ?? [])),
            'account_name' => $data['account_name'],
            'account_number' => $data['account_no'],
            'bank_name' => $data['bank_name'],
            'ifsc_code' => strtoupper($data['ifsc_code']),
            'account_type' => $data['account_type'],
            'status' => 'active',
            'aadhar_front_path' => StoresUploadedFiles::store($request->file('aadhar_front'), 'vendors/aadhar/front'),
            'aadhar_back_path' => StoresUploadedFiles::store($request->file('aadhar_back'), 'vendors/aadhar/back'),
            'cover_image_path' => $this->storeOptionalImage($request, ['cover_image', 'coverImage'], 'vendors/cover-images'),
            'profile_image_path' => $this->storeOptionalImage($request, ['profile_image'], 'vendors/profile-images'),
            'shop_logo_path' => $this->storeOptionalImage($request, ['shop_logo'], 'vendors/shop-logos'),
            'pan_card_path' => $this->storeOptionalImage($request, ['pan_card'], 'vendors/pan-cards'),
        ]);

        StoresActorFcmToken::saveForActor($vendor, $fcmToken);

        $token = $vendor->createToken('vendor-api')->plainTextToken;

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->otp->formatActor(OtpService::ACTOR_VENDOR, $vendor),
        ], 'Vendor registration successful.', 201);
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

        if ($request->filled('service_types')) {
            $request->merge([
                'service_types' => VendorValidationRules::prepareServiceTypesInput($request->input('service_types')),
            ]);
        }

        $data = $request->validate(array_merge(
            $this->vendorFieldRules(required: false, vendorId: $vendor->id),
            [
                'profile_image' => ['sometimes', ...self::IMAGE_RULE],
                'cover_image' => ['sometimes', ...self::IMAGE_RULE],
                'coverImage' => ['sometimes', ...self::IMAGE_RULE],
                'bio' => ['sometimes', 'nullable', 'string', 'max:2000'],
                'is_available' => ['sometimes', 'boolean'],
            ]
        ), VendorValidationRules::messages(), VendorValidationRules::attributes());

        $vendor->fill($this->mapVendorAttributes($data));

        if (array_key_exists('bio', $data)) {
            $vendor->bio = $data['bio'];
        }

        if (array_key_exists('is_available', $data)) {
            $vendor->is_listing_active = $data['is_available'];
        }

        $this->applyVendorFiles($vendor, $request);
        $vendor->save();

        return $this->success([
            'user' => $this->otp->formatActor(OtpService::ACTOR_VENDOR, $vendor->fresh()),
        ], 'Profile updated.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->success(null, 'Logged out.');
    }

    private function prepareRegisterInput(Request $request): void
    {
        $merge = [];

        if (! $request->filled('shop_name') && $request->filled('brand_name')) {
            $merge['shop_name'] = $request->input('brand_name');
        }

        if ($request->has('service_types')) {
            $merge['service_types'] = VendorValidationRules::prepareServiceTypesInput($request->input('service_types'));
        }

        if ($request->hasFile('coverImage') && ! $request->hasFile('cover_image')) {
            $request->files->set('cover_image', $request->file('coverImage'));
        }

        if ($merge !== []) {
            $request->merge($merge);
        }
    }

    /** @param  list<string>  $keys */
    private function storeOptionalImage(Request $request, array $keys, string $directory): ?string
    {
        foreach ($keys as $key) {
            if ($request->hasFile($key)) {
                return StoresUploadedFiles::store($request->file($key), $directory);
            }
        }

        return null;
    }

    private function vendorFieldRules(bool $required, ?int $vendorId = null): array
    {
        $rule = $required ? 'required' : 'sometimes';

        $mobileRules = [$required ? 'nullable' : 'sometimes', 'string', 'max:20'];

        if ($vendorId) {
            $mobileRules[] = Rule::unique('vendors', 'mobile')->ignore($vendorId);
        }

        return [
            'shop_name' => [$required ? 'required_without:brand_name' : 'sometimes', 'nullable', 'string', 'max:100', 'regex:'.AdminValidationRules::REGEX_TITLE],
            'brand_name' => [$required ? 'required_without:shop_name' : 'sometimes', 'nullable', 'string', 'max:100', 'regex:'.AdminValidationRules::REGEX_TITLE],
            'owner_name' => [$rule, 'string', 'max:100', 'regex:'.AdminValidationRules::REGEX_PERSON_NAME],
            'email' => array_merge([$rule], array_slice(AdminValidationRules::emailRules(false), 1)),
            'mobile_no' => $vendorId ? $mobileRules : ['prohibited'],
            'service_types' => [$rule, 'array', 'min:1'],
            'service_types.*' => ['string', 'max:100', Rule::in(VendorValidationRules::SERVICE_TYPES)],
            'business_mobile' => [$required ? 'nullable' : 'sometimes', 'nullable', 'string', 'regex:'.AdminValidationRules::REGEX_PHONE],
            'business_mail' => array_merge([$required ? 'nullable' : 'sometimes'], array_slice(AdminValidationRules::emailRules(false), 1)),
            'aadhar_number' => ['nullable', 'string', 'size:12', 'regex:/^[2-9][0-9]{11}$/'],
            'gst_no' => ['nullable', 'string', 'size:15', 'regex:'.AdminValidationRules::REGEX_GST],
            'address' => [$required ? 'nullable' : 'sometimes', 'nullable', 'string', 'max:500'],
            'country' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'country_id' => ['nullable'],
            'country_other' => ['nullable', 'required_if:country_id,other', 'string', 'max:100'],
            'state_id' => ['nullable'],
            'state_other' => ['nullable', 'required_if:state_id,other', 'required_if:country_id,other', 'string', 'max:100'],
            'city_id' => ['nullable'],
            'city_other' => ['nullable', 'required_if:city_id,other', 'required_if:state_id,other', 'required_if:country_id,other', 'string', 'max:100'],
            'pincode' => [$required ? 'nullable' : 'sometimes', 'nullable', 'string', 'max:10'],
            'aadhar_front' => [$rule, ...self::IMAGE_RULE],
            'aadhar_back' => [$rule, ...self::IMAGE_RULE],
            'shop_logo' => ['sometimes', 'nullable', ...self::IMAGE_RULE],
            'pan_card' => ['sometimes', 'nullable', ...self::IMAGE_RULE],
            'account_name' => [$rule, 'string', 'max:255'],
            'account_no' => [$rule, 'string', 'max:20'],
            'ifsc_code' => [$rule, 'string', 'max:11'],
            'bank_name' => [$rule, 'string', 'max:255'],
            'account_type' => [$rule, 'in:savings,current'],
        ];
    }

    private function mapVendorAttributes(array $data): array
    {
        if (isset($data['country_id']) || isset($data['state_id']) || isset($data['city_id'])
            || isset($data['country_other']) || isset($data['state_other']) || isset($data['city_other'])) {
            $data = array_merge($data, LocationResolver::resolve($data));
        }

        $attributes = collect($data)->only([
            'owner_name',
            'email',
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
            'aadhar_number',
        ])->all();

        if (isset($data['shop_name'])) {
            $attributes['shop_name'] = $data['shop_name'];
            $attributes['brand_name'] = $data['brand_name'] ?? $data['shop_name'];
        } elseif (isset($data['brand_name'])) {
            $attributes['brand_name'] = $data['brand_name'];
            $attributes['shop_name'] = $data['brand_name'];
        }

        if (isset($data['service_types'])) {
            $attributes['service_types'] = implode(', ', VendorValidationRules::normalizeServiceTypes($data['service_types']));
        }

        if (isset($data['business_mail'])) {
            $attributes['business_email'] = $data['business_mail'];
        }

        if (array_key_exists('gst_no', $data)) {
            $attributes['gst_number'] = $data['gst_no'] ? strtoupper($data['gst_no']) : null;
        }

        if (isset($data['account_no'])) {
            $attributes['account_number'] = $data['account_no'];
        }

        if (isset($data['ifsc_code'])) {
            $attributes['ifsc_code'] = strtoupper($data['ifsc_code']);
        }

        if (isset($data['mobile_no'])) {
            $attributes['mobile'] = $this->otp->normalizeMobile($data['mobile_no']);
        }

        return $attributes;
    }

    private function applyVendorFiles(Vendor $vendor, Request $request): void
    {
        $files = [
            'profile_image' => ['column' => 'profile_image_path', 'dir' => 'vendors/profile-images'],
            'cover_image' => ['column' => 'cover_image_path', 'dir' => 'vendors/cover-images'],
            'coverImage' => ['column' => 'cover_image_path', 'dir' => 'vendors/cover-images'],
            'aadhar_front' => ['column' => 'aadhar_front_path', 'dir' => 'vendors/aadhar/front'],
            'aadhar_back' => ['column' => 'aadhar_back_path', 'dir' => 'vendors/aadhar/back'],
            'shop_logo' => ['column' => 'shop_logo_path', 'dir' => 'vendors/shop-logos'],
            'pan_card' => ['column' => 'pan_card_path', 'dir' => 'vendors/pan-cards'],
        ];

        $processedColumns = [];

        foreach ($files as $input => $config) {
            if (in_array($config['column'], $processedColumns, true)) {
                continue;
            }

            if (! $request->hasFile($input)) {
                continue;
            }

            $vendor->{$config['column']} = StoresUploadedFiles::replace(
                $request->file($input),
                $vendor->{$config['column']},
                $config['dir']
            );

            $processedColumns[] = $config['column'];
        }
    }
}
