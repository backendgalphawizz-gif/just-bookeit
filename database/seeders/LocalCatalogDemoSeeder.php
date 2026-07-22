<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\PortfolioItem;
use App\Models\PortfolioItemDamageDeduction;
use App\Models\PortfolioItemVariant;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Local-only catalog fixtures: categories, subcategories, products + variants.
 *
 * Run: php artisan db:seed --class=LocalCatalogDemoSeeder
 */
class LocalCatalogDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ProductOptionSeeder::class);

        $mains = $this->seedMainCategories();
        $services = $this->seedServiceCategories();
        $subs = $this->seedSubcategories($mains);
        $vendor = $this->ensureDemoVendor();
        $secondVendor = $this->ensureSecondDemoVendor($vendor);

        $dressId = $services['rented-dress']->id;
        $jewelleryId = $services['rented-jewellery']->id;
        $designerId = $services['fashion-designer']->id;

        foreach ($this->productsForVendorOne($dressId, $jewelleryId, $designerId, $subs) as $index => $data) {
            $this->upsertProduct($vendor, $data, $index);
        }

        $this->upgradeExistingSareeProduct($vendor, $dressId, $subs['women-sarees']->id);

        foreach ($this->productsForVendorTwo($dressId, $jewelleryId, $designerId, $subs) as $index => $data) {
            $this->upsertProduct($secondVendor, $data, $index + 100);
        }

        $this->upgradeExistingVendorTwoProducts($secondVendor, $dressId, $designerId, $subs['women-sarees']->id, $subs['women-lehengas']->id);

        $this->cleanupUnwantedCategories($mains, $services, $subs);

        $this->command?->info('Local catalog demo seeded.');
        $this->command?->info('Vendor 1: '.$vendor->brand_name.' (id '.$vendor->id.') @ '.$vendor->latitude.','.$vendor->longitude);
        $this->command?->info('Vendor 2: '.$secondVendor->brand_name.' (id '.$secondVendor->id.') @ '.$secondVendor->latitude.','.$secondVendor->longitude);
        $this->command?->info('Clean categories kept: Women/Men/Kids + their subcategories + 3 service types.');
    }

    /**
     * @param  array<string, Category>  $subs
     * @return list<array<string, mixed>>
     */
    protected function productsForVendorOne(int $dressId, int $jewelleryId, int $designerId, array $subs): array
    {
        return [
            [
                'title' => 'Banarasi Silk Saree',
                'description' => 'Classic Banarasi silk saree with zari border. Ideal for weddings and festive events.',
                'category_id' => $dressId,
                'subcategory_id' => $subs['women-sarees']->id,
                'audience' => 'women',
                'image_seed' => 'banarasi-saree',
                'variants' => [
                    ['size' => 'Free Size', 'color' => 'Maroon', 'price' => 2499, 'advance_amount' => 1000, 'quantity' => 3],
                    ['size' => 'Free Size', 'color' => 'Red', 'price' => 2699, 'advance_amount' => 1200, 'quantity' => 2],
                    ['size' => 'Free Size', 'color' => 'Gold', 'price' => 2999, 'advance_amount' => 1500, 'quantity' => 1],
                ],
                'damage_deductions' => [
                    ['damage_type' => 'Minor stain', 'percent' => 10],
                    ['damage_type' => 'Border tear', 'percent' => 35],
                ],
            ],
            [
                'title' => 'Kanjivaram Wedding Saree',
                'description' => 'Heavy Kanjivaram weave with rich pallu. Rent for bridal and reception looks.',
                'category_id' => $dressId,
                'subcategory_id' => $subs['women-sarees']->id,
                'audience' => 'women',
                'image_seed' => 'kanjivaram-saree',
                'variants' => [
                    ['size' => 'Free Size', 'color' => 'Pink', 'price' => 3999, 'advance_amount' => 2000, 'quantity' => 2],
                    ['size' => 'Free Size', 'color' => 'Navy Blue', 'price' => 4299, 'advance_amount' => 2200, 'quantity' => 1],
                ],
                'damage_deductions' => [
                    ['damage_type' => 'Pallu damage', 'percent' => 40],
                ],
            ],
            [
                'title' => 'Pastel Party Lehenga',
                'description' => 'Lightweight pastel lehenga with blouse and dupatta. Perfect for sangeet nights.',
                'category_id' => $dressId,
                'subcategory_id' => $subs['women-lehengas']->id,
                'audience' => 'women',
                'image_seed' => 'pastel-lehenga',
                'variants' => [
                    ['size' => 'S', 'color' => 'Pink', 'price' => 3499, 'advance_amount' => 1500, 'quantity' => 2],
                    ['size' => 'M', 'color' => 'Pink', 'price' => 3499, 'advance_amount' => 1500, 'quantity' => 3],
                    ['size' => 'L', 'color' => 'Ivory', 'price' => 3699, 'advance_amount' => 1600, 'quantity' => 2],
                    ['size' => 'XL', 'color' => 'Ivory', 'price' => 3699, 'advance_amount' => 1600, 'quantity' => 1],
                ],
                'damage_deductions' => [
                    ['damage_type' => 'Embroidery snag', 'percent' => 20],
                ],
            ],
            [
                'title' => 'Sequin Evening Gown',
                'description' => 'Floor-length sequin gown for cocktail and reception events.',
                'category_id' => $dressId,
                'subcategory_id' => $subs['women-gowns']->id,
                'audience' => 'women',
                'image_seed' => 'sequin-gown',
                'variants' => [
                    ['size' => 'S', 'color' => 'Black', 'price' => 2899, 'advance_amount' => 1200, 'quantity' => 1],
                    ['size' => 'M', 'color' => 'Black', 'price' => 2899, 'advance_amount' => 1200, 'quantity' => 2],
                    ['size' => 'M', 'color' => 'Red', 'price' => 3099, 'advance_amount' => 1300, 'quantity' => 1],
                    ['size' => 'L', 'color' => 'Red', 'price' => 3099, 'advance_amount' => 1300, 'quantity' => 1],
                ],
            ],
            [
                'title' => 'Embroidered Festive Kurti Set',
                'description' => 'Comfort kurti with palazzo for daytime functions.',
                'category_id' => $dressId,
                'subcategory_id' => $subs['women-kurtis']->id,
                'audience' => 'women',
                'image_seed' => 'festive-kurti',
                'variants' => [
                    ['size' => 'S', 'color' => 'Blue', 'price' => 999, 'advance_amount' => 400, 'quantity' => 4],
                    ['size' => 'M', 'color' => 'Blue', 'price' => 999, 'advance_amount' => 400, 'quantity' => 5],
                    ['size' => 'L', 'color' => 'Green', 'price' => 1099, 'advance_amount' => 450, 'quantity' => 3],
                    ['size' => 'XL', 'color' => 'Green', 'price' => 1099, 'advance_amount' => 450, 'quantity' => 2],
                ],
            ],
            [
                'title' => 'Classic Sherwani Set',
                'description' => 'Indo-western sherwani with churidar. Ideal for groomsmen and festive wear.',
                'category_id' => $dressId,
                'subcategory_id' => $subs['men-sherwanis']->id,
                'audience' => 'men',
                'image_seed' => 'classic-sherwani',
                'variants' => [
                    ['size' => 'M', 'color' => 'Ivory', 'price' => 4599, 'advance_amount' => 2000, 'quantity' => 2],
                    ['size' => 'L', 'color' => 'Ivory', 'price' => 4599, 'advance_amount' => 2000, 'quantity' => 2],
                    ['size' => 'XL', 'color' => 'Maroon', 'price' => 4799, 'advance_amount' => 2200, 'quantity' => 1],
                ],
            ],
            [
                'title' => 'Designer Mens Kurta',
                'description' => 'Cotton-silk kurta for engagement and festive dinners.',
                'category_id' => $dressId,
                'subcategory_id' => $subs['men-kurtas']->id,
                'audience' => 'men',
                'image_seed' => 'mens-kurta',
                'variants' => [
                    ['size' => 'M', 'color' => 'White', 'price' => 1499, 'advance_amount' => 600, 'quantity' => 3],
                    ['size' => 'L', 'color' => 'White', 'price' => 1499, 'advance_amount' => 600, 'quantity' => 3],
                    ['size' => 'L', 'color' => 'Navy Blue', 'price' => 1599, 'advance_amount' => 650, 'quantity' => 2],
                    ['size' => 'XL', 'color' => 'Navy Blue', 'price' => 1599, 'advance_amount' => 650, 'quantity' => 1],
                ],
            ],
            [
                'title' => 'Kids Party Lehenga Choli',
                'description' => 'Cute embroidered lehenga choli for kids birthday and festivals.',
                'category_id' => $dressId,
                'subcategory_id' => $subs['kids-party-wear']->id,
                'audience' => 'kids',
                'image_seed' => 'kids-lehenga',
                'variants' => [
                    ['size' => 'XS', 'color' => 'Pink', 'price' => 899, 'advance_amount' => 350, 'quantity' => 2],
                    ['size' => 'S', 'color' => 'Pink', 'price' => 899, 'advance_amount' => 350, 'quantity' => 2],
                    ['size' => 'S', 'color' => 'Gold', 'price' => 999, 'advance_amount' => 400, 'quantity' => 1],
                ],
            ],
            [
                'title' => 'Temple Jewellery Set',
                'description' => 'Necklace + earrings set for traditional saree looks.',
                'category_id' => $jewelleryId,
                'subcategory_id' => $subs['women-sarees']->id,
                'audience' => 'women',
                'image_seed' => 'temple-jewellery',
                'variants' => [
                    ['size' => 'One Size', 'color' => 'Gold', 'price' => 1299, 'advance_amount' => 800, 'quantity' => 4],
                    ['size' => 'One Size', 'color' => 'Rose Gold', 'price' => 1399, 'advance_amount' => 850, 'quantity' => 2],
                ],
            ],
            [
                'title' => 'Custom Bridal Consultation',
                'description' => 'Fashion designer consult for bridal look styling and fittings.',
                'category_id' => $designerId,
                'subcategory_id' => $subs['women-lehengas']->id,
                'audience' => 'women',
                'image_seed' => 'bridal-consult',
                'price_per_day' => 5000,
                'advance_amount' => 2000,
                'variants' => [],
            ],
        ];
    }

    /**
     * @param  array<string, Category>  $subs
     * @return list<array<string, mixed>>
     */
    protected function productsForVendorTwo(int $dressId, int $jewelleryId, int $designerId, array $subs): array
    {
        return [
            [
                'title' => 'Chanderi Cotton Saree',
                'description' => 'Lightweight Chanderi saree with delicate border. Easy drape for daytime events.',
                'category_id' => $dressId,
                'subcategory_id' => $subs['women-sarees']->id,
                'audience' => 'women',
                'image_seed' => 'v2-chanderi-saree',
                'variants' => [
                    ['size' => 'Free Size', 'color' => 'Ivory', 'price' => 1799, 'advance_amount' => 700, 'quantity' => 3],
                    ['size' => 'Free Size', 'color' => 'Pink', 'price' => 1899, 'advance_amount' => 750, 'quantity' => 2],
                    ['size' => 'Free Size', 'color' => 'Blue', 'price' => 1999, 'advance_amount' => 800, 'quantity' => 2],
                ],
                'damage_deductions' => [
                    ['damage_type' => 'Minor stain', 'percent' => 12],
                ],
            ],
            [
                'title' => 'Organza Party Saree',
                'description' => 'Sheer organza saree with sequin work for evening functions.',
                'category_id' => $dressId,
                'subcategory_id' => $subs['women-sarees']->id,
                'audience' => 'women',
                'image_seed' => 'v2-organza-saree',
                'variants' => [
                    ['size' => 'Free Size', 'color' => 'Black', 'price' => 2299, 'advance_amount' => 900, 'quantity' => 2],
                    ['size' => 'Free Size', 'color' => 'Silver', 'price' => 2499, 'advance_amount' => 1000, 'quantity' => 1],
                ],
            ],
            [
                'title' => 'Floral Net Lehenga',
                'description' => 'Flowy net lehenga with floral embroidery and matching dupatta.',
                'category_id' => $dressId,
                'subcategory_id' => $subs['women-lehengas']->id,
                'audience' => 'women',
                'image_seed' => 'v2-floral-lehenga',
                'variants' => [
                    ['size' => 'S', 'color' => 'Red', 'price' => 3299, 'advance_amount' => 1400, 'quantity' => 1],
                    ['size' => 'M', 'color' => 'Red', 'price' => 3299, 'advance_amount' => 1400, 'quantity' => 2],
                    ['size' => 'L', 'color' => 'Maroon', 'price' => 3499, 'advance_amount' => 1500, 'quantity' => 1],
                ],
            ],
            [
                'title' => 'Satin Slip Gown',
                'description' => 'Minimal satin gown for cocktail nights.',
                'category_id' => $dressId,
                'subcategory_id' => $subs['women-gowns']->id,
                'audience' => 'women',
                'image_seed' => 'v2-satin-gown',
                'variants' => [
                    ['size' => 'S', 'color' => 'Navy Blue', 'price' => 2599, 'advance_amount' => 1100, 'quantity' => 1],
                    ['size' => 'M', 'color' => 'Navy Blue', 'price' => 2599, 'advance_amount' => 1100, 'quantity' => 2],
                    ['size' => 'L', 'color' => 'Black', 'price' => 2699, 'advance_amount' => 1200, 'quantity' => 1],
                ],
            ],
            [
                'title' => 'Printed Cotton Kurti',
                'description' => 'Everyday festive cotton kurti with soft print.',
                'category_id' => $dressId,
                'subcategory_id' => $subs['women-kurtis']->id,
                'audience' => 'women',
                'image_seed' => 'v2-cotton-kurti',
                'variants' => [
                    ['size' => 'S', 'color' => 'White', 'price' => 799, 'advance_amount' => 300, 'quantity' => 4],
                    ['size' => 'M', 'color' => 'White', 'price' => 799, 'advance_amount' => 300, 'quantity' => 4],
                    ['size' => 'L', 'color' => 'Blue', 'price' => 849, 'advance_amount' => 350, 'quantity' => 3],
                    ['size' => 'XL', 'color' => 'Blue', 'price' => 849, 'advance_amount' => 350, 'quantity' => 2],
                ],
            ],
            [
                'title' => 'Indo Western Suit',
                'description' => 'Modern Indo-western suit for receptions and parties.',
                'category_id' => $dressId,
                'subcategory_id' => $subs['men-suits']->id,
                'audience' => 'men',
                'image_seed' => 'v2-indo-suit',
                'variants' => [
                    ['size' => 'M', 'color' => 'Black', 'price' => 3999, 'advance_amount' => 1800, 'quantity' => 2],
                    ['size' => 'L', 'color' => 'Black', 'price' => 3999, 'advance_amount' => 1800, 'quantity' => 2],
                    ['size' => 'XL', 'color' => 'Navy Blue', 'price' => 4199, 'advance_amount' => 1900, 'quantity' => 1],
                ],
            ],
            [
                'title' => 'Kids Ethnic Kurta Set',
                'description' => 'Boys festive kurta pajama set for family functions.',
                'category_id' => $dressId,
                'subcategory_id' => $subs['kids-ethnic-wear']->id,
                'audience' => 'kids',
                'image_seed' => 'v2-kids-kurta',
                'variants' => [
                    ['size' => 'XS', 'color' => 'White', 'price' => 699, 'advance_amount' => 250, 'quantity' => 2],
                    ['size' => 'S', 'color' => 'White', 'price' => 699, 'advance_amount' => 250, 'quantity' => 3],
                    ['size' => 'S', 'color' => 'Maroon', 'price' => 749, 'advance_amount' => 300, 'quantity' => 2],
                ],
            ],
            [
                'title' => 'Kundan Necklace Set',
                'description' => 'Kundan necklace with matching earrings for bridal and festive looks.',
                'category_id' => $jewelleryId,
                'subcategory_id' => $subs['women-sarees']->id,
                'audience' => 'women',
                'image_seed' => 'v2-kundan-set',
                'variants' => [
                    ['size' => 'One Size', 'color' => 'Gold', 'price' => 1599, 'advance_amount' => 900, 'quantity' => 3],
                    ['size' => 'One Size', 'color' => 'Silver', 'price' => 1499, 'advance_amount' => 850, 'quantity' => 2],
                ],
            ],
            [
                'title' => 'Groom Styling Session',
                'description' => 'Designer styling consultation for groom and family outfits.',
                'category_id' => $designerId,
                'subcategory_id' => $subs['men-sherwanis']->id,
                'audience' => 'men',
                'image_seed' => 'v2-groom-styling',
                'price_per_day' => 3500,
                'advance_amount' => 1500,
                'variants' => [],
            ],
        ];
    }

    /** @return array<string, Category> */
    protected function seedMainCategories(): array
    {
        $definitions = [
            'women' => ['name' => 'Women', 'slug' => 'women', 'sort' => 1],
            'men' => ['name' => 'Men', 'slug' => 'men', 'sort' => 2],
            'kids' => ['name' => 'Kids', 'slug' => 'kids', 'sort' => 3],
        ];

        $out = [];

        foreach ($definitions as $key => $def) {
            $category = Category::query()
                ->where('type', Category::TYPE_MAIN)
                ->where(function ($q) use ($def, $key) {
                    $q->where('slug', $def['slug'])
                        ->orWhere('slug', $key === 'men' ? 'mens' : $def['slug'])
                        ->orWhereRaw('LOWER(name) = ?', [strtolower($def['name'])])
                        ->orWhereRaw('LOWER(name) = ?', [$key === 'men' ? 'mens' : strtolower($def['name'])]);
                })
                ->first();

            if ($category) {
                $category->update([
                    'name' => $def['name'],
                    'slug' => $def['slug'],
                    'is_active' => true,
                    'sort_order' => $def['sort'],
                ]);
            } else {
                $category = Category::query()->create([
                    'name' => $def['name'],
                    'slug' => $def['slug'],
                    'type' => Category::TYPE_MAIN,
                    'is_active' => true,
                    'sort_order' => $def['sort'],
                ]);
            }

            $out[$key] = $category->fresh();
        }

        return $out;
    }

    /** @return array<string, Category> */
    protected function seedServiceCategories(): array
    {
        $definitions = [
            'fashion-designer' => 'Fashion Designer',
            'rented-dress' => 'Rented Dress',
            'rented-jewellery' => 'Rented Jewellery',
        ];

        $out = [];
        $sort = 1;

        foreach ($definitions as $slug => $name) {
            $out[$slug] = Category::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'type' => Category::TYPE_SERVICE,
                    'is_active' => true,
                    'sort_order' => $sort++,
                    'parent_id' => null,
                ]
            );
        }

        return $out;
    }

    /**
     * @param  array<string, Category>  $mains
     * @return array<string, Category>
     */
    protected function seedSubcategories(array $mains): array
    {
        $map = [
            'women-sarees' => ['parent' => 'women', 'name' => 'Sarees', 'aliases' => ['sarees', 'women-sarees']],
            'women-lehengas' => ['parent' => 'women', 'name' => 'Lehengas', 'aliases' => ['women-lehengas', 'lehengas']],
            'women-gowns' => ['parent' => 'women', 'name' => 'Gowns', 'aliases' => ['women-gowns', 'gowns']],
            'women-kurtis' => ['parent' => 'women', 'name' => 'Kurtis', 'aliases' => ['women-kurtis', 'kurtis']],
            'men-kurtas' => ['parent' => 'men', 'name' => 'Kurtas', 'aliases' => ['men-kurtas', 'mens-kurta', 'kurtas']],
            'men-sherwanis' => ['parent' => 'men', 'name' => 'Sherwanis', 'aliases' => ['men-sherwanis', 'sherwanis']],
            'men-suits' => ['parent' => 'men', 'name' => 'Suits', 'aliases' => ['men-suits', 'suits']],
            'kids-ethnic-wear' => ['parent' => 'kids', 'name' => 'Ethnic Wear', 'aliases' => ['kids-ethnic-wear', 'ethnic-wear']],
            'kids-party-wear' => ['parent' => 'kids', 'name' => 'Party Wear', 'aliases' => ['kids-party-wear']],
        ];

        $out = [];
        $sortByParent = [];

        foreach ($map as $key => $def) {
            $parent = $mains[$def['parent']];
            $sortByParent[$parent->id] = ($sortByParent[$parent->id] ?? 0) + 1;
            $slug = $key;

            $existing = Category::query()
                ->where('type', Category::TYPE_SUB)
                ->where(function ($q) use ($def, $slug) {
                    $q->whereIn('slug', array_values(array_unique(array_merge([$slug], $def['aliases']))));
                })
                ->first();

            if ($existing) {
                $existing->update([
                    'name' => $def['name'],
                    'slug' => $slug,
                    'parent_id' => $parent->id,
                    'is_active' => true,
                    'sort_order' => $sortByParent[$parent->id],
                ]);
                $out[$key] = $existing->fresh();
            } else {
                $out[$key] = Category::query()->create([
                    'name' => $def['name'],
                    'slug' => $slug,
                    'type' => Category::TYPE_SUB,
                    'parent_id' => $parent->id,
                    'is_active' => true,
                    'sort_order' => $sortByParent[$parent->id],
                ]);
            }
        }

        return $out;
    }

    protected function ensureDemoVendor(): Vendor
    {
        $vendor = Vendor::query()
            ->where('status', 'active')
            ->where('is_listing_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('id')
            ->first();

        if ($vendor) {
            return $vendor;
        }

        $vendor = Vendor::query()->orderBy('id')->first();

        if (! $vendor) {
            $vendor = Vendor::query()->create([
                'vendor_code' => 'VENLOCAL01',
                'brand_name' => 'Indore Demo Studio',
                'owner_name' => 'Demo Owner',
                'mobile' => '9000000001',
                'email' => 'demo-vendor@example.com',
                'city' => 'Indore',
                'categories' => ['Fashion Designer', 'Rented Dress', 'Rented Jewellery'],
                'status' => 'active',
                'is_listing_active' => true,
                'approved_at' => now(),
            ]);
        }

        $vendor->forceFill([
            'status' => 'active',
            'is_listing_active' => true,
            'city' => $vendor->city ?: 'Indore',
            'latitude' => $vendor->latitude ?: 22.7196,
            'longitude' => $vendor->longitude ?: 75.8577,
            'approved_at' => $vendor->approved_at ?: now(),
        ])->save();

        return $vendor->fresh();
    }

    protected function ensureSecondDemoVendor(Vendor $firstVendor): Vendor
    {
        $vendor = Vendor::query()
            ->where('id', '!=', $firstVendor->id)
            ->where('status', 'active')
            ->orderBy('id')
            ->first();

        if (! $vendor) {
            $vendor = Vendor::query()->create([
                'vendor_code' => 'VENLOCAL02',
                'brand_name' => 'Tanmay Demo Atelier',
                'owner_name' => 'Tanmay',
                'mobile' => '9000000002',
                'email' => 'demo-vendor-2@example.com',
                'city' => 'Indore',
                'categories' => ['Fashion Designer', 'Rented Dress', 'Rented Jewellery'],
                'status' => 'active',
                'is_listing_active' => true,
                'approved_at' => now(),
            ]);
        }

        // Slightly offset from vendor 1 so both stay inside typical Indore discovery radius.
        $vendor->forceFill([
            'status' => 'active',
            'is_listing_active' => true,
            'city' => $vendor->city ?: 'Indore',
            'latitude' => $vendor->latitude ?: 22.7250,
            'longitude' => $vendor->longitude ?: 75.8650,
            'approved_at' => $vendor->approved_at ?: now(),
        ])->save();

        return $vendor->fresh();
    }

    /** @param  array<string, mixed>  $data */
    protected function upsertProduct(Vendor $vendor, array $data, int $index): PortfolioItem
    {
        $item = PortfolioItem::query()->updateOrCreate(
            [
                'vendor_id' => $vendor->id,
                'title' => $data['title'],
            ],
            [
                'category_id' => $data['category_id'],
                'subcategory_id' => $data['subcategory_id'],
                'audience' => $data['audience'],
                'description' => $data['description'],
                'image_url' => 'https://picsum.photos/seed/'.Str::slug($data['image_seed']).'/800/1000',
                'price_per_day' => $data['price_per_day'] ?? null,
                'advance_amount' => $data['advance_amount'] ?? null,
                'status' => 'approved',
                'is_listing_active' => true,
                'rejection_reason' => null,
                'reviewed_at' => now()->subDays(1),
            ]
        );

        $item->variants()->delete();
        $item->damageDeductions()->delete();

        foreach ($data['variants'] as $sort => $variant) {
            PortfolioItemVariant::query()->create([
                'portfolio_item_id' => $item->id,
                'size' => $variant['size'] ?? '',
                'color' => $variant['color'] ?? '',
                'price' => $variant['price'],
                'advance_amount' => $variant['advance_amount'] ?? null,
                'quantity' => $variant['quantity'] ?? 1,
                'image_path' => null,
                'sort_order' => $sort + 1,
            ]);
        }

        foreach (($data['damage_deductions'] ?? []) as $sort => $deduction) {
            PortfolioItemDamageDeduction::query()->create([
                'portfolio_item_id' => $item->id,
                'damage_type' => $deduction['damage_type'],
                'percent' => $deduction['percent'],
                'sort_order' => $sort + 1,
            ]);
        }

        $item->refreshDressPricingFromVariants();

        return $item->fresh(['variants']);
    }

    protected function upgradeExistingSareeProduct(Vendor $vendor, int $dressCategoryId, int $sareeSubId): void
    {
        $existing = PortfolioItem::query()
            ->where('title', 'Coat set one piece')
            ->first();

        if (! $existing) {
            return;
        }

        $existing->update([
            'vendor_id' => $vendor->id,
            'category_id' => $dressCategoryId,
            'subcategory_id' => $sareeSubId,
            'audience' => 'women',
            'status' => 'approved',
            'is_listing_active' => true,
            'description' => $existing->description ?: 'Demo rented dress product under Women → Sarees with size/color variants.',
        ]);

        if ($existing->variants()->count() === 0) {
            foreach ([
                ['size' => 'M', 'color' => 'Blue', 'price' => 1999, 'advance_amount' => 800, 'quantity' => 2],
                ['size' => 'L', 'color' => 'Red', 'price' => 2199, 'advance_amount' => 900, 'quantity' => 1],
            ] as $sort => $variant) {
                PortfolioItemVariant::query()->create([
                    'portfolio_item_id' => $existing->id,
                    'size' => $variant['size'],
                    'color' => $variant['color'],
                    'price' => $variant['price'],
                    'advance_amount' => $variant['advance_amount'],
                    'quantity' => $variant['quantity'],
                    'sort_order' => $sort + 1,
                ]);
            }
        } else {
            foreach ($existing->variants as $sort => $variant) {
                $variant->update([
                    'advance_amount' => $variant->advance_amount ?? 800,
                    'quantity' => $variant->quantity ?? 2,
                    'sort_order' => $sort + 1,
                ]);
            }
        }

        $existing->refreshDressPricingFromVariants();
    }

    protected function upgradeExistingVendorTwoProducts(
        Vendor $vendor,
        int $dressCategoryId,
        int $designerCategoryId,
        int $sareeSubId,
        int $lehengaSubId
    ): void {
        $jacket = PortfolioItem::query()->where('title', 'Jacket')->first();
        if ($jacket) {
            $jacket->update([
                'vendor_id' => $vendor->id,
                'category_id' => $designerCategoryId,
                'subcategory_id' => $lehengaSubId,
                'audience' => 'women',
                'status' => 'approved',
                'is_listing_active' => true,
                'price_per_day' => $jacket->price_per_day ?: 1000,
                'description' => $jacket->description ?: 'Designer jacket styling product for vendor 2.',
            ]);
        }

        $necklace = PortfolioItem::query()->where('title', 'Necklace')->first();
        if ($necklace && (int) $necklace->vendor_id === (int) $vendor->id) {
            $necklace->update([
                'status' => 'approved',
                'is_listing_active' => true,
                'audience' => 'women',
                'subcategory_id' => $sareeSubId,
            ]);
        }
    }

    /**
     * Keep only the intended main/service/sub categories in admin.
     *
     * @param  array<string, Category>  $mains
     * @param  array<string, Category>  $services
     * @param  array<string, Category>  $subs
     */
    protected function cleanupUnwantedCategories(array $mains, array $services, array $subs): void
    {
        $keepIds = collect($mains)
            ->merge($services)
            ->merge($subs)
            ->pluck('id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Re-point any stray products off junk subcategories before delete.
        $fallbackSubId = $subs['women-sarees']->id ?? null;
        if ($fallbackSubId) {
            PortfolioItem::query()
                ->whereNotNull('subcategory_id')
                ->whereNotIn('subcategory_id', $keepIds)
                ->update(['subcategory_id' => $fallbackSubId, 'audience' => 'women']);
        }

        // Delete junk subs first, then unused mains (Girl/Boys/etc).
        Category::query()
            ->where('type', Category::TYPE_SUB)
            ->whereNotIn('id', $keepIds)
            ->get()
            ->each(function (Category $category) {
                if ($category->portfolioItems()->exists()) {
                    return;
                }

                $category->delete();
                $this->command?->warn('Removed junk subcategory: '.$category->name);
            });

        Category::query()
            ->where('type', Category::TYPE_MAIN)
            ->whereNotIn('id', $keepIds)
            ->get()
            ->each(function (Category $category) {
                if ($category->children()->exists()) {
                    $category->children()
                        ->whereDoesntHave('portfolioItems')
                        ->each(fn (Category $child) => $child->delete());
                }

                if ($category->children()->exists() || $category->portfolioItems()->exists()) {
                    $category->update(['is_active' => false]);
                    $this->command?->warn('Deactivated unused main category: '.$category->name);

                    return;
                }

                if (PortfolioItem::query()->where('category_id', $category->id)->exists()) {
                    $category->update(['is_active' => false]);

                    return;
                }

                $category->delete();
                $this->command?->warn('Removed unused main category: '.$category->name);
            });

        Category::query()
            ->where('type', Category::TYPE_SERVICE)
            ->whereNotIn('id', $keepIds)
            ->get()
            ->each(function (Category $category) {
                if (
                    $category->orders()->exists()
                    || PortfolioItem::query()->where('category_id', $category->id)->exists()
                    || Vendor::query()->whereJsonContains('categories', $category->name)->exists()
                ) {
                    $category->update(['is_active' => false]);

                    return;
                }

                $category->delete();
                $this->command?->warn('Removed unused service category: '.$category->name);
            });
    }
}
