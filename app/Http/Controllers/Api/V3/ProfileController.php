<?php

namespace App\Http\Controllers\Api\V3;

use App\Models\Faq;
use App\Models\PlatformSetting;
use App\Services\PlatformConfigService;
use App\Support\Api\DriverApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends DriverApiController
{
    public function __construct(
        protected PlatformConfigService $config
    ) {}

    public function index(Request $request): JsonResponse
    {
        $driver = $this->driver($request);

        return $this->success([
            'driver' => DriverApiPresenter::driverSummary($driver),
            'menu' => [
                ['key' => 'personal_information', 'label' => 'Personal Information', 'route' => '/api/v3/auth/profile'],
                ['key' => 'documents', 'label' => 'Documents', 'route' => '/api/v3/profile/documents'],
                ['key' => 'payments', 'label' => 'Payment', 'route' => '/api/v3/payments'],
                ['key' => 'privacy_policy', 'label' => 'Privacy Policy', 'route' => '/api/v3/profile/pages'],
                ['key' => 'terms', 'label' => 'Terms & Conditions', 'route' => '/api/v3/profile/pages'],
                ['key' => 'faqs', 'label' => 'FAQs', 'route' => '/api/v3/profile/pages'],
            ],
            'counts' => [
                'assigned_deliveries' => $driver->orders()->where('status', 'in_progress')->count(),
                'completed_deliveries' => $driver->orders()->where('status', 'delivered')->count(),
            ],
        ]);
    }

    public function documents(Request $request): JsonResponse
    {
        $driver = $this->driver($request);

        return $this->success([
            'documents' => [
                'aadhar_front_url' => $driver->aadharFrontUrl(),
                'aadhar_back_url' => $driver->aadharBackUrl(),
                'driving_licence_url' => $driver->drivingLicenceUrl(),
            ],
            'bank' => [
                'account_name' => $driver->account_name,
                'account_no' => $driver->account_number,
                'ifsc_code' => $driver->ifsc_code,
                'bank_name' => $driver->bank_name,
                'account_type' => $driver->account_type,
            ],
        ]);
    }

    public function pages(): JsonResponse
    {
        $legal = $this->config->legalFor(Faq::AUDIENCE_DRIVER);

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
}
