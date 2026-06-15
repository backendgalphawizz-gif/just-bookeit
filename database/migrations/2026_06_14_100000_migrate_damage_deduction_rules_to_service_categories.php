<?php

use App\Models\Category;
use App\Models\PlatformSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $rules = PlatformSetting::get('refund_damage_deduction_rules', []);

        if (! is_array($rules) || $rules === []) {
            return;
        }

        $serviceCategories = Category::query()->service()->get()->keyBy('id');
        $bySlug = $serviceCategories->keyBy(fn (Category $category) => $category->slug);
        $byName = $serviceCategories->keyBy(fn (Category $category) => strtolower($category->name));

        $aliases = [
            'jewellery' => 'rented-jewellery',
            'jewelry' => 'rented-jewellery',
            'rented jewellery' => 'rented-jewellery',
            'cloth' => 'rented-dress',
            'clothes' => 'rented-dress',
            'dress' => 'rented-dress',
            'rented dress' => 'rented-dress',
            'fashion designer' => 'fashion-designer',
            'designer' => 'fashion-designer',
        ];

        $converted = collect($rules)->map(function (array $rule) use ($bySlug, $byName, $aliases, $serviceCategories) {
            if (isset($rule['service_category_id'])) {
                return [
                    'service_category_id' => (int) $rule['service_category_id'],
                    'max_percent' => (float) ($rule['max_percent'] ?? 0),
                ];
            }

            $label = strtolower(trim((string) ($rule['product_type'] ?? '')));
            $slug = $aliases[$label] ?? str($label)->slug()->toString();
            $category = $bySlug->get($slug) ?? $byName->get($label);

            if (! $category && $label === 'other') {
                $category = $bySlug->get('rented-dress') ?? $serviceCategories->first();
            }

            if (! $category) {
                return null;
            }

            return [
                'service_category_id' => $category->id,
                'max_percent' => (float) ($rule['max_percent'] ?? 0),
            ];
        })->filter()->unique('service_category_id')->values();

        foreach ($serviceCategories as $category) {
            if (! $converted->contains(fn (array $rule) => $rule['service_category_id'] === $category->id)) {
                $converted->push([
                    'service_category_id' => $category->id,
                    'max_percent' => 100.0,
                ]);
            }
        }

        PlatformSetting::set(
            'refund_damage_deduction_rules',
            json_encode($converted->values()->all()),
            'refund_rules',
            'json'
        );
    }

    public function down(): void
    {
        // Legacy text-based rules are not restored automatically.
    }
};
