<?php

namespace App\Support;

use Illuminate\Validation\Rule;

class VendorValidationRules
{
    /** Max image upload size in kilobytes (20 MB). */
    public const MAX_IMAGE_KB = 20480;

    public const SERVICE_TYPES = [
        'Fashion Designer',
        'Rental Dresses',
        'Rental Jewelry',
    ];

    public static function messages(): array
    {
        return AdminValidationRules::messages();
    }

    public static function attributes(): array
    {
        return array_merge(AdminValidationRules::attributes(), [
            'business_mail' => 'business email ID',
            'gst_no' => 'GSTIN',
            'account_no' => 'account number',
            'shop_name' => 'shop/business name',
            'portfolio_image' => 'portfolio image',
        ]);
    }

    public static function mobile(bool $required = true, ?int $ignoreVendorId = null): array
    {
        $rules = [$required ? 'required' : 'nullable', 'string', 'regex:'.AdminValidationRules::REGEX_PHONE];

        if ($ignoreVendorId) {
            $rules[] = Rule::unique('vendors', 'mobile')->ignore($ignoreVendorId);
        }

        return ['mobile' => $rules];
    }

    public static function profile(int $vendorId): array
    {
        return [
            'owner_name' => ['required', 'string', 'max:100', 'regex:'.AdminValidationRules::REGEX_PERSON_NAME],
            'email' => AdminValidationRules::emailRules(true, [Rule::unique('vendors', 'email')->ignore($vendorId)]),
            'mobile' => ['nullable', 'string', 'regex:'.AdminValidationRules::REGEX_PHONE, Rule::unique('vendors', 'mobile')->ignore($vendorId)],
            'profile_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.self::MAX_IMAGE_KB],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.self::MAX_IMAGE_KB],
        ];
    }

    public static function business(): array
    {
        return [
            'shop_name' => ['required', 'string', 'max:100', 'regex:'.AdminValidationRules::REGEX_TITLE],
            'service_types' => ['nullable', 'array', 'min:1'],
            'service_types.*' => ['string', 'max:100', Rule::in(self::SERVICE_TYPES)],
            'business_mobile' => ['nullable', 'string', 'regex:'.AdminValidationRules::REGEX_PHONE],
            'business_mail' => AdminValidationRules::emailRules(false),
            'gst_no' => ['nullable', 'string', 'size:15', 'regex:'.AdminValidationRules::REGEX_GST],
            'address' => ['nullable', 'string', 'max:500', 'regex:'.AdminValidationRules::REGEX_TEXT],
        ];
    }

    public static function bank(): array
    {
        return [
            'account_name' => ['nullable', 'string', 'max:255', 'regex:'.AdminValidationRules::REGEX_PERSON_NAME],
            'account_no' => ['nullable', 'string', 'max:20', 'regex:'.AdminValidationRules::REGEX_ACCOUNT_NUMBER],
            'bank_name' => ['nullable', 'string', 'max:255', 'regex:'.AdminValidationRules::REGEX_TITLE],
            'ifsc_code' => ['nullable', 'string', 'size:11', 'regex:'.AdminValidationRules::REGEX_IFSC],
            'account_type' => ['nullable', 'in:savings,current'],
        ];
    }

    public static function register(): array
    {
        return [
            'shop_name' => ['required', 'string', 'max:100', 'regex:'.AdminValidationRules::REGEX_TITLE],
            'owner_name' => ['required', 'string', 'max:100', 'regex:'.AdminValidationRules::REGEX_PERSON_NAME],
            'email' => AdminValidationRules::emailRules(true, ['unique:vendors,email']),
            'city' => ['nullable', 'string', 'max:100', 'regex:'.AdminValidationRules::REGEX_CITY],
            'service_types' => ['required', 'string', 'max:500', 'regex:'.AdminValidationRules::REGEX_TEXT],
            'aadhar_front' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'aadhar_back' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ];
    }

    public static function product(bool $creating): array
    {
        $priceRule = $creating ? 'required' : 'sometimes';

        return [
            'title' => ['required', 'string', 'max:255', 'regex:'.AdminValidationRules::REGEX_TITLE],
            'description' => ['nullable', 'string', 'max:5000', 'regex:'.AdminValidationRules::REGEX_TEXT],
            'price_per_day' => [$priceRule, 'numeric', 'min:0', 'max:9999999'],
            'advance_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'audience' => ['nullable', 'in:women,men,kids'],
            ...\App\Support\SubcategoryCatalog::subcategoryIdRules($creating),
            'variants' => ['nullable', 'array', 'max:50'],
            'variants.*.size' => ['required_with:variants', 'string', 'max:50', 'regex:'.AdminValidationRules::REGEX_TITLE],
            'variants.*.color' => ['required_with:variants', 'string', 'max:100', 'regex:'.AdminValidationRules::REGEX_TITLE],
            'variants.*.price' => ['required_with:variants', 'numeric', 'min:0', 'max:9999999'],
            'damage_deductions' => ['nullable', 'array', 'max:20'],
            'damage_deductions.*.damage_type' => ['required_with:damage_deductions', 'string', 'max:100', 'regex:'.AdminValidationRules::REGEX_TITLE],
            'damage_deductions.*.percent' => ['required_with:damage_deductions', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /** @return array<string, array<int, string>> */
    public static function productTypeRules(): array
    {
        return [
            'type' => ['nullable', 'string', Rule::in(['fashion-designer', 'rented-dress', 'rented-jewellery'])],
        ];
    }

    /** @return array<string, int> */
    public static function productUploadLimits(): array
    {
        return [
            'image' => 1,
            'product_image' => 1,
            'gallery_images' => 10,
            'images' => 10,
            'variant_images' => 50,
        ];
    }

    /** @return list<string> */
    public static function productUploadRules(): array
    {
        return ['image', 'mimes:jpeg,jpg,png,webp', 'max:'.self::MAX_IMAGE_KB];
    }

    public static function portfolioUpload(): array
    {
        return [
            'audience' => ['required', 'in:women,men,kids'],
            'portfolio_image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.self::MAX_IMAGE_KB],
        ];
    }
}
