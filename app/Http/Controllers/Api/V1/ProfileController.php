<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Customer;
use App\Support\Api\CustomerApiPresenter;
use App\Models\Faq;
use App\Models\PlatformSetting;
use App\Services\PlatformConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends ApiController
{
    public function __construct(
        protected PlatformConfigService $config
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        return $this->success([
            'user' => CustomerApiPresenter::customerProfile($customer),
            'menu' => [
                ['key' => 'edit_profile', 'label' => 'Edit Profile'],
                ['key' => 'booking_history', 'label' => 'Booking History', 'route' => '/v1/bookings'],
                ['key' => 'measurements', 'label' => 'Measurements', 'route' => '/v1/measurements'],
                ['key' => 'addresses', 'label' => 'Address', 'route' => '/v1/addresses'],
                ['key' => 'about_us', 'label' => 'About Us', 'route' => '/v1/profile/pages'],
                ['key' => 'help_support', 'label' => 'Help & Support', 'route' => '/v1/support-tickets'],
                ['key' => 'privacy_policy', 'label' => 'Privacy Policy', 'route' => '/v1/profile/pages'],
                ['key' => 'terms', 'label' => 'Terms & Conditions', 'route' => '/v1/profile/pages'],
                ['key' => 'faqs', 'label' => 'FAQs', 'route' => '/v1/profile/pages'],
            ],
            'counts' => [
                'bookings' => $customer->orders()->count(),
                'measurements' => $customer->measurements()->count(),
                'addresses' => $customer->addresses()->count(),
                'support_tickets' => $customer->supportTickets()->count(),
                'unread_chats' => $customer->conversations()
                    ->whereHas('messages', fn ($q) => $q->where('sender_type', 'vendor')->whereNull('read_at'))
                    ->count(),
            ],
        ]);
    }

    public function pages(): JsonResponse
    {
        $legal = $this->config->legalFor(Faq::AUDIENCE_USER);

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
