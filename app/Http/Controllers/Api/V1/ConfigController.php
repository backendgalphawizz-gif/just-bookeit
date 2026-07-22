<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Services\Payment\RazorpayService;
use App\Services\PlatformConfigService;
use Illuminate\Http\JsonResponse;

class ConfigController extends ApiController
{
    public function __construct(
        protected PlatformConfigService $config
    ) {}

    public function index(): JsonResponse
    {
        $data = $this->config->fullConfig();

        // Always expose public Razorpay credentials for the customer app.
        $data['razorpay'] = app(RazorpayService::class)->publicClientConfig();

        return $this->success($data);
    }
}
