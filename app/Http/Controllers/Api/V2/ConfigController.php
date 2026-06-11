<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\ApiController;
use App\Support\VendorValidationRules;
use Illuminate\Http\JsonResponse;

class ConfigController extends ApiController
{
    public function index(): JsonResponse
    {
        return $this->success([
            'product_categories' => [
                ['type' => 'rented-dress', 'label' => 'Rental Dresses'],
                ['type' => 'rented-jewellery', 'label' => 'Rental Jewellery'],
                ['type' => 'fashion-designer', 'label' => 'Fashion Designer'],
            ],
            'product_audiences' => [
                ['key' => 'women', 'label' => 'Women'],
                ['key' => 'men', 'label' => 'Men'],
                ['key' => 'kids', 'label' => 'Kids'],
            ],
            'product_sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
            'product_colors' => [
                'Black', 'White', 'Red', 'Blue', 'Green', 'Pink', 'Gold', 'Silver', 'Maroon', 'Ivory', 'Navy Blue', 'Rose Gold',
            ],
            'portfolio_audiences' => [
                ['key' => 'women', 'label' => 'Women'],
                ['key' => 'men', 'label' => 'Men'],
                ['key' => 'kids', 'label' => 'Kids'],
            ],
            'service_types' => VendorValidationRules::SERVICE_TYPES,
            'booking_tabs' => [
                ['key' => 'accepted', 'label' => 'Accepted'],
                ['key' => 'in_transit', 'label' => 'In Transit'],
                ['key' => 'new', 'label' => 'New'],
            ],
            'payment_types' => [
                ['key' => 'credit', 'label' => 'Credit'],
                ['key' => 'debit', 'label' => 'Debit'],
            ],
        ]);
    }
}
