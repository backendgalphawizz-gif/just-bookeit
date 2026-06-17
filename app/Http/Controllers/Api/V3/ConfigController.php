<?php

namespace App\Http\Controllers\Api\V3;

use App\Models\Faq;
use App\Services\PlatformConfigService;
use Illuminate\Http\JsonResponse;

class ConfigController extends DriverApiController
{
    public function __construct(
        protected PlatformConfigService $config
    ) {}

    public function index(): JsonResponse
    {
        return $this->success([
            'delivery_tabs' => [
                ['key' => 'new', 'label' => 'New'],
                ['key' => 'accepted', 'label' => 'Accepted'],
                ['key' => 'pickup', 'label' => 'Pickup'],
                ['key' => 'dispatched', 'label' => 'Dispatched'],
                ['key' => 'delivered', 'label' => 'Delivered'],
                ['key' => 'cancelled', 'label' => 'Cancelled'],
                ['key' => 'rescheduled', 'label' => 'Rescheduled'],
            ],
            'support' => [
                'email' => \App\Models\PlatformSetting::get('support_email'),
                'phone' => \App\Models\PlatformSetting::get('support_phone'),
            ],
            'legal' => $this->config->legalFor(Faq::AUDIENCE_DRIVER),
        ]);
    }
}
