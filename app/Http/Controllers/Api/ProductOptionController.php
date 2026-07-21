<?php

namespace App\Http\Controllers\Api;

use App\Support\ProductOptionCatalog;
use Illuminate\Http\JsonResponse;

class ProductOptionController extends ApiController
{
    public function sizes(): JsonResponse
    {
        return $this->success([
            'items' => ProductOptionCatalog::sizeApiItems(),
            'names' => ProductOptionCatalog::sizeNames(),
        ]);
    }

    public function colors(): JsonResponse
    {
        return $this->success([
            'items' => ProductOptionCatalog::colorApiItems(),
            'names' => ProductOptionCatalog::colorNames(),
        ]);
    }
}
