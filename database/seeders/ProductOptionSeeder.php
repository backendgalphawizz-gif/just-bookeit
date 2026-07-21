<?php

namespace Database\Seeders;

use App\Models\ProductColor;
use App\Models\ProductSize;
use App\Support\ProductOptionCatalog;
use Illuminate\Database\Seeder;

class ProductOptionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (ProductOptionCatalog::DEFAULT_SIZES as $index => $name) {
            ProductSize::query()->updateOrCreate(
                ['name' => $name],
                [
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }

        $order = 0;
        foreach (ProductOptionCatalog::DEFAULT_COLORS as $name => $hex) {
            $order++;
            ProductColor::query()->updateOrCreate(
                ['name' => $name],
                [
                    'hex_code' => $hex,
                    'sort_order' => $order,
                    'is_active' => true,
                ]
            );
        }
    }
}
