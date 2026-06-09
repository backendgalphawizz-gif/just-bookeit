<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use App\Models\Role;
use App\Support\AdminValidationRules;
use Illuminate\Validation\Validator;

class VendorRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return AdminValidationRules::vendor($this->route('vendor')?->id);
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
        $data = $this->safe()->except([
            'category_ids',
            'profile_image',
            'shop_logos',
            'remove_shop_logo_ids',
            'aadhar_front',
            'aadhar_back',
            'pan_card',
        ]);

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
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $data['categories'] = $categoryIds->isEmpty()
            ? []
            : Category::query()->whereIn('id', $categoryIds)->orderBy('name')->pluck('name')->all();

        $data['rating'] = $data['rating'] ?? 0;
        $data['orders_completed'] = $data['orders_completed'] ?? 0;
        $data['earnings'] = $data['earnings'] ?? 0;

        return $data;
    }
}
