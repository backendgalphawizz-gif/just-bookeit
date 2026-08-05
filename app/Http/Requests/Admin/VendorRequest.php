<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use App\Models\Role;
use App\Support\AdminValidationRules;
use App\Support\LocationResolver;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class VendorRequest extends AdminFormRequest
{
    public function rules(): array
    {
        $rules = AdminValidationRules::vendor($this->route('vendor')?->id);
        $vendor = $this->route('vendor');

        if ($vendor) {
            $rules['status'] = ['required', Rule::in([$vendor->status])];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('brand_name') && ! $this->filled('shop_name')) {
                $validator->errors()->add('brand_name', 'Brand name or shop name is required.');
            }
        });
    }

    public function vendorData(): array
    {
        $vendor = $this->route('vendor');

        $data = $this->safe()->except([
            'category_ids',
            'service_category_ids',
            'country_id',
            'country_other',
            'state_id',
            'state_other',
            'city_id',
            'city_other',
            'profile_image',
            'shop_logo',
            'shop_images',
            'remove_shop_image_ids',
            'aadhar_front',
            'aadhar_back',
            'pan_card',
        ]);

        if ($this->filled('country_id') || $this->filled('state_id') || $this->filled('city_id')
            || $this->filled('country_other') || $this->filled('state_other') || $this->filled('city_other')) {
            $data = array_merge($data, LocationResolver::resolve($this->all()));
        }

        $shopName = trim((string) ($data['shop_name'] ?? ''));
        $brandName = trim((string) ($data['brand_name'] ?? ''));

        if ($shopName !== '') {
            $data['shop_name'] = $shopName;
            $data['brand_name'] = $brandName !== '' ? $brandName : $shopName;
        } elseif ($brandName !== '') {
            $data['brand_name'] = $brandName;
            $data['shop_name'] = $brandName;
        }

        $categoryIds = collect($this->input('category_ids', []))
            ->merge($this->input('service_category_ids', []))
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $selectedServiceCategories = $categoryIds->isEmpty()
            ? collect()
            : Category::query()
                ->whereIn('id', $categoryIds)
                ->where('type', Category::TYPE_SERVICE)
                ->get(['id', 'name']);

        $selectedNames = $selectedServiceCategories->pluck('name');

        // Audience categories (Men/Women/Kids) are not editable from this form,
        // so keep whatever the vendor already has instead of wiping them.
        $retainedNames = $vendor
            ? Category::query()
                ->whereIn('name', (array) ($vendor->categories ?? []))
                ->where('type', '!=', Category::TYPE_SERVICE)
                ->pluck('name')
            : collect();

        $data['categories'] = $retainedNames
            ->merge($selectedNames)
            ->unique()
            ->sort()
            ->values()
            ->all();

        // Keep vendor-app `service_types` in sync (canonical labels / aliases).
        $data['service_types'] = implode(
            ', ',
            \App\Support\VendorValidationRules::normalizeServiceTypes($selectedNames->all())
        );

        $data['rating'] = $data['rating'] ?? 0;
        $data['orders_completed'] = $data['orders_completed'] ?? 0;
        $data['earnings'] = $data['earnings'] ?? 0;

        return $data;
    }
}
