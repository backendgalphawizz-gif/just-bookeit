<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class ServiceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $serviceCategories = [
            ['name' => 'Fashion Designer', 'slug' => 'fashion-designer'],
            ['name' => 'Rented Dress', 'slug' => 'rented-dress'],
            ['name' => 'Rented Jewellery', 'slug' => 'rented-jewellery'],
        ];

        foreach ($serviceCategories as $index => $data) {
            Category::query()->updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'type' => Category::TYPE_SERVICE,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                    'parent_id' => null,
                ]
            );
        }
    }
}
