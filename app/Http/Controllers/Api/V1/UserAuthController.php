<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Customer;
use App\Services\Auth\OtpService;
use App\Support\AdminValidationRules;
use App\Support\CodeGenerator;
use App\Support\StoresUploadedFiles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserAuthController extends ApiController
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

        $payload = $this->otp->send(OtpService::ACTOR_CUSTOMER, $data['mobile'], $data['type']);

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
            OtpService::ACTOR_CUSTOMER,
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
            $this->customerFieldRules(required: true)
        ));

        $mobile = $this->otp->consumeRegistrationToken(OtpService::ACTOR_CUSTOMER, $data['registration_token']);

        if (isset($data['mobile_no']) && $this->otp->normalizeMobile($data['mobile_no']) !== $mobile) {
            throw ValidationException::withMessages([
                'mobile_no' => ['Mobile number must match OTP verified number.'],
            ]);
        }

        if (Customer::query()->where('mobile', $mobile)->exists()) {
            return $this->error('Account already exists. Please login.', 409);
        }

        $customer = Customer::query()->create([
            ...$this->mapCustomerAttributes($data),
            'customer_code' => CodeGenerator::customerCode(),
            'mobile' => $mobile,
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

    public function updateProfile(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        $data = $request->validate(array_merge(
            $this->customerFieldRules(required: false, customerId: $customer->id),
            ['profile_image' => ['sometimes', ...self::IMAGE_RULE]]
        ));

        $customer->fill($this->mapCustomerAttributes($data));

        if ($request->hasFile('profile_image')) {
            $customer->profile_image_path = StoresUploadedFiles::replace(
                $request->file('profile_image'),
                $customer->profile_image_path,
                'customers/profile-images'
            );
        }

        $customer->save();

        return $this->success([
            'user' => $this->otp->formatActor(OtpService::ACTOR_CUSTOMER, $customer->fresh()),
        ], 'Profile updated.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->success(null, 'Logged out.');
    }

    private function customerFieldRules(bool $required, ?int $customerId = null): array
    {
        $rule = $required ? 'required' : 'sometimes';

        $mobileRules = [$required ? 'nullable' : 'sometimes', 'string', 'max:20'];

        if ($customerId) {
            $mobileRules[] = Rule::unique('customers', 'mobile')->ignore($customerId);
        }

        return [
            'name' => [$rule, 'string', 'max:255'],
            'email' => AdminValidationRules::emailRules(false),
            'city' => ['nullable', 'string', 'max:100'],
            'mobile_no' => $mobileRules,
        ];
    }

    private function mapCustomerAttributes(array $data): array
    {
        $attributes = collect($data)->only(['name', 'email', 'city'])->all();

        if (isset($data['mobile_no'])) {
            $attributes['mobile'] = $this->otp->normalizeMobile($data['mobile_no']);
        }

        return $attributes;
    }
}
