<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Services\PlatformConfigService;
use Illuminate\Http\JsonResponse;

class ConfigController extends ApiController
{
    public function __construct(
        protected PlatformConfigService $config
    ) {}

    public function index(): JsonResponse
    {
        return $this->success($this->config->fullConfig());
    }
}
