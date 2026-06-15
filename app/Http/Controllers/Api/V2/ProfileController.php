<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\Faq;
use App\Models\PlatformSetting;
use App\Services\PlatformConfigService;
use App\Support\Api\VendorApiPresenter;
use App\Support\StoresUploadedFiles;
use App\Support\VendorValidationRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends VendorApiController
{
    public function __construct(
        protected PlatformConfigService $config
    ) {}

    public function index(Request $request): JsonResponse
    {
        $vendor = $this->vendor($request);

        return $this->success([
            'vendor' => VendorApiPresenter::vendorAccount($vendor),
            'menu' => [
                ['key' => 'edit_profile', 'label' => 'Edit Profile', 'route' => '/api/v2/auth/profile'],
                ['key' => 'portfolio', 'label' => 'Portfolio', 'route' => '/api/v2/portfolio'],
                ['key' => 'business_info', 'label' => 'Business Info', 'route' => '/api/v2/profile/business'],
                ['key' => 'bank_information', 'label' => 'Bank Information', 'route' => '/api/v2/profile/bank'],
                ['key' => 'payment_history', 'label' => 'Payment History', 'route' => '/api/v2/payments'],
                ['key' => 'privacy_policy', 'label' => 'Privacy Policy', 'route' => '/api/v2/profile/pages'],
                ['key' => 'terms', 'label' => 'Terms & Conditions', 'route' => '/api/v2/profile/pages'],
                ['key' => 'faqs', 'label' => 'FAQs', 'route' => '/api/v2/profile/pages'],
            ],
            'counts' => [
                'bookings' => $vendor->orders()->count(),
                'products' => $vendor->portfolioItems()->count(),
                'unread_chats' => $vendor->conversations()
                    ->whereHas('messages', fn ($q) => $q->where('sender_type', 'customer')->whereNull('read_at'))
                    ->count(),
            ],
        ]);
    }

    public function business(Request $request): JsonResponse
    {
        $vendor = $this->vendor($request);

        return $this->success([
            'business' => VendorApiPresenter::vendorBusiness($vendor),
            'service_type_options' => VendorValidationRules::SERVICE_TYPES,
        ]);
    }

    public function bank(Request $request): JsonResponse
    {
        $vendor = $this->vendor($request);

        return $this->success([
            'bank' => VendorApiPresenter::vendorBank($vendor),
        ]);
    }

    public function pages(): JsonResponse
    {
        $legal = $this->config->legalFor(Faq::AUDIENCE_VENDOR);

        return $this->success([
            'about_us' => (string) PlatformSetting::get('about_us', ''),
            'help_support' => (string) PlatformSetting::get('help_support', ''),
            'terms_and_conditions' => $legal['terms_and_conditions'],
            'privacy_policy' => $legal['privacy_policy'],
            'faq' => $legal['faq'],
            'contact' => [
                'email' => PlatformSetting::get('support_email'),
                'phone' => PlatformSetting::get('support_phone'),
                'address' => PlatformSetting::get('contact_address'),
            ],
        ]);
    }

    public function updateBio(Request $request): JsonResponse
    {
        $vendor = $this->vendor($request);

        $data = $this->validateVendor($request, [
            'bio' => ['required', 'string', 'max:2000'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.VendorValidationRules::MAX_IMAGE_KB],
        ]);

        $vendor->bio = $data['bio'];

        if ($request->hasFile('cover_image')) {
            $vendor->cover_image_path = StoresUploadedFiles::replace(
                $request->file('cover_image'),
                $vendor->cover_image_path,
                'vendors/cover-images'
            );
        }

        $vendor->save();

        return $this->success([
            'vendor' => VendorApiPresenter::vendorSummary($vendor->fresh()),
            'bio' => $vendor->bio,
            'cover_image_url' => $vendor->coverImageUrl(),
        ], 'Bio updated.');
    }

    public function updateBusiness(Request $request): JsonResponse
    {
        $vendor = $this->vendor($request);
        $data = $this->validateVendor($request, VendorValidationRules::business());

        $vendor->fill([
            'shop_name' => $data['shop_name'],
            'brand_name' => $data['shop_name'],
            'service_types' => isset($data['service_types'])
                ? implode(', ', $data['service_types'])
                : $vendor->service_types,
            'business_mobile' => $data['business_mobile'] ?? null,
            'business_email' => $data['business_mail'] ?? null,
            'gst_number' => $data['gst_no'] ?? null,
            'address' => $data['address'] ?? null,
        ]);
        $vendor->save();

        return $this->success([
            'vendor' => VendorApiPresenter::vendorSummary($vendor->fresh()),
        ], 'Business info updated.');
    }

    public function updateBank(Request $request): JsonResponse
    {
        $vendor = $this->vendor($request);
        $data = $this->validateVendor($request, VendorValidationRules::bank());

        $vendor->fill([
            'account_name' => $data['account_name'] ?? null,
            'account_number' => $data['account_no'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'ifsc_code' => $data['ifsc_code'] ?? null,
            'account_type' => $data['account_type'] ?? null,
        ]);
        $vendor->save();

        return $this->success([
            'bank' => [
                'account_name' => $vendor->account_name,
                'account_number' => $vendor->account_number,
                'bank_name' => $vendor->bank_name,
                'ifsc_code' => $vendor->ifsc_code,
                'account_type' => $vendor->account_type,
            ],
        ], 'Bank information updated.');
    }

    public function toggleAvailability(Request $request): JsonResponse
    {
        $data = $request->validate([
            'is_available' => ['required', 'boolean'],
        ]);

        return $this->setAvailability($request, (bool) $data['is_available']);
    }

    public function markAvailable(Request $request): JsonResponse
    {
        return $this->setAvailability($request, true);
    }

    public function markUnavailable(Request $request): JsonResponse
    {
        return $this->setAvailability($request, false);
    }

    protected function setAvailability(Request $request, bool $isAvailable): JsonResponse
    {
        $vendor = $this->vendor($request);
        $vendor->is_listing_active = $isAvailable;
        $vendor->save();

        return $this->success([
            'vendor' => VendorApiPresenter::vendorSummary($vendor->fresh()),
            'is_available' => $isAvailable,
        ], $isAvailable ? 'You are now available.' : 'You are now unavailable.');
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $vendor = $this->vendor($request);

        $data = $this->validateVendor($request, [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $vendor->password = $data['password'];
        $vendor->save();

        return $this->success(null, 'Password updated.');
    }
}
