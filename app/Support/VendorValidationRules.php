<?php

namespace App\Support;

use App\Support\SubcategoryCatalog;
use Illuminate\Validation\Rule;

class VendorValidationRules
{
    /** Max image upload size in kilobytes (20 MB). */
    public const MAX_IMAGE_KB = 20480;

    /** Max video upload size in kilobytes (100 MB). */
    public const MAX_VIDEO_KB = 102400;

    /** @var list<string> */
    public const VIDEO_MIMES = [
        'mp4', 'mov', 'avi', 'mkv', 'webm', '3gp', 'mpeg', 'mpg', 'm4v', 'wmv', 'flv', 'ogv', 'ts', 'm2ts',
    ];

    public const SERVICE_TYPES = [
        'Fashion Designer',
        'Rental Dresses',
        'Rental Jewelry',
    ];

    /**
     * Maps stored service type labels (including legacy aliases) to product type slugs.
     *
     * @var array<string, string>
     */
    public const SERVICE_TYPE_SLUGS = [
        'Fashion Designer' => 'fashion-designer',
        'Rental Dresses' => 'rented-dress',
        'Rental Jewelry' => 'rented-jewellery',
        'Rented Dresses' => 'rented-dress',
        'Rented Jewelry' => 'rented-jewellery',
        'Rented Dress' => 'rented-dress',
        'Rented Jewellery' => 'rented-jewellery',
        'Rental Jewellery' => 'rented-jewellery',
    ];

    /** @return list<string> */
    public static function serviceTypeSlugs(array $serviceTypes): array
    {
        $slugs = [];

        foreach ($serviceTypes as $type) {
            $key = trim((string) $type);
            if ($key === '') {
                continue;
            }

            $slug = self::SERVICE_TYPE_SLUGS[$key] ?? null;
            if ($slug && ! in_array($slug, $slugs, true)) {
                $slugs[] = $slug;
            }
        }

        return $slugs;
    }

    /**
     * Normalize stored labels (including legacy aliases) to canonical SERVICE_TYPES values.
     *
     * @return list<string>
     */
    public static function normalizeServiceTypes(array $serviceTypes): array
    {
        $slugToLabel = [
            'fashion-designer' => 'Fashion Designer',
            'rented-dress' => 'Rental Dresses',
            'rented-jewellery' => 'Rental Jewelry',
        ];

        $normalized = [];

        foreach (self::serviceTypeSlugs($serviceTypes) as $slug) {
            $label = $slugToLabel[$slug] ?? null;
            if ($label && ! in_array($label, $normalized, true)) {
                $normalized[] = $label;
            }
        }

        return $normalized;
    }

    public static function messages(): array
    {
        $maxMb = UploadLimits::formatMegabytes(self::MAX_IMAGE_KB * 1024);

        return array_merge(AdminValidationRules::messages(), [
            'reason.required' => 'A rejection reason is required.',
            'reason.min' => 'Rejection reason must be at least 5 characters.',
            'reason.max' => 'Rejection reason cannot exceed 500 characters.',
            'aadhar_front.required' => 'Please upload the Aadhaar front image.',
            'aadhar_back.required' => 'Please upload the Aadhaar back image.',
            'aadhar_front.uploaded' => 'Aadhaar front could not be uploaded. Use a JPEG/PNG under '.$maxMb.' MB (phone photos are often too large — compress or resize first).',
            'aadhar_back.uploaded' => 'Aadhaar back could not be uploaded. Use a JPEG/PNG under '.$maxMb.' MB (phone photos are often too large — compress or resize first).',
            'aadhar_front.max' => 'Aadhaar front is too large. Maximum size is '.$maxMb.' MB.',
            'aadhar_back.max' => 'Aadhaar back is too large. Maximum size is '.$maxMb.' MB.',
            'aadhar_front.image' => 'Aadhaar front must be a JPEG, PNG, or WebP image.',
            'aadhar_back.image' => 'Aadhaar back must be a JPEG, PNG, or WebP image.',
            'aadhar_front.mimes' => 'Aadhaar front must be a JPEG, PNG, or WebP image.',
            'aadhar_back.mimes' => 'Aadhaar back must be a JPEG, PNG, or WebP image.',
        ]);
    }

    public static function attributes(): array
    {
        return array_merge(AdminValidationRules::attributes(), [
            'business_mail' => 'business email ID',
            'gst_no' => 'GSTIN',
            'account_no' => 'account number',
            'shop_name' => 'shop/business name',
            'aadhar_front' => 'aadhaar front',
            'aadhar_back' => 'aadhaar back',
            'portfolio_image' => 'portfolio image',
            'videos' => 'product videos',
            'gallery_videos' => 'product videos',
            'product_videos' => 'product videos',
            'reason' => 'rejection reason',
        ]);
    }

    /** Effective per-file max in bytes (app limit capped by PHP upload_max_filesize). */
    public static function effectiveMaxImageBytes(): int
    {
        return min(self::MAX_IMAGE_KB * 1024, UploadLimits::uploadMaxFilesizeBytes());
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
            'service_types' => ['required', 'array', 'min:1'],
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

    public static function bookingReject(): array
    {
        return [
            'reason' => ['required', 'string', 'min:5', 'max:500', 'regex:'.AdminValidationRules::REGEX_TEXT],
        ];
    }

    public static function bookingDamage(): array
    {
        return [
            'damage_note' => ['nullable', 'string', 'max:255', 'regex:'.AdminValidationRules::REGEX_TEXT],
            'damage_deduct_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public static function register(): array
    {
        $image = ['image', 'mimes:jpeg,jpg,png,webp', 'max:'.self::MAX_IMAGE_KB];

        return [
            'owner_name' => ['required', 'string', 'max:100', 'regex:'.AdminValidationRules::REGEX_PERSON_NAME],
            'mobile' => ['required', 'string', 'regex:'.AdminValidationRules::REGEX_PHONE, 'unique:vendors,mobile'],
            'email' => AdminValidationRules::emailRules(true, ['unique:vendors,email']),
            'aadhar_front' => ['required', ...$image],
            'aadhar_back' => ['required', ...$image],
            'cover_image' => ['nullable', ...$image],
            'profile_image' => ['nullable', ...$image],
            'shop_name' => ['required', 'string', 'max:100', 'regex:'.AdminValidationRules::REGEX_TITLE],
            'service_types' => ['required', 'array', 'min:1'],
            'service_types.*' => ['string', 'max:100', Rule::in(self::SERVICE_TYPES)],
            'business_mobile' => ['nullable', 'string', 'regex:'.AdminValidationRules::REGEX_PHONE],
            'business_mail' => AdminValidationRules::emailRules(false),
            'aadhar_number' => ['nullable', 'string', 'size:12', 'regex:/^[2-9][0-9]{11}$/'],
            'gst_no' => ['nullable', 'string', 'size:15', 'regex:'.AdminValidationRules::REGEX_GST],
            'address' => ['nullable', 'string', 'max:500', 'regex:'.AdminValidationRules::REGEX_TEXT],
            'city' => ['nullable', 'string', 'max:100', 'regex:'.AdminValidationRules::REGEX_CITY],
            'state' => ['nullable', 'string', 'max:100', 'regex:'.AdminValidationRules::REGEX_CITY],
            'country' => ['nullable', 'string', 'max:100', 'regex:'.AdminValidationRules::REGEX_CITY],
            'pincode' => ['nullable', 'string', 'max:10', 'regex:/^[1-9][0-9]{5}$/'],
            'shop_logo' => ['nullable', ...$image],
            'pan_card' => ['nullable', ...$image],
            'account_name' => ['required', 'string', 'max:255', 'regex:'.AdminValidationRules::REGEX_PERSON_NAME],
            'account_no' => ['required', 'string', 'max:20', 'regex:'.AdminValidationRules::REGEX_ACCOUNT_NUMBER],
            'bank_name' => ['required', 'string', 'max:255', 'regex:'.AdminValidationRules::REGEX_TITLE],
            'ifsc_code' => ['required', 'string', 'size:11', 'regex:'.AdminValidationRules::REGEX_IFSC],
            'account_type' => ['required', 'in:savings,current'],
        ];
    }

    public static function product(bool $creating): array
    {
        $priceRule = $creating ? 'required' : 'sometimes';

        return [
            'title' => ['required', 'string', 'max:255', 'regex:'.AdminValidationRules::REGEX_TITLE],
            'description' => ['nullable', 'string', 'max:5000', 'regex:'.AdminValidationRules::REGEX_TEXT],
            'price_per_day' => [$priceRule, 'numeric', 'min:0', 'max:9999999'],
            'audience' => ['nullable', 'in:women,men,kids'],
            ...SubcategoryCatalog::subcategoryIdRules($creating),
            ...SubcategoryCatalog::mainCategoryIdRules(false),
            'main_category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('type', 'main')],
            'shop_category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('type', 'main')],
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
            'videos' => 5,
            'gallery_videos' => 5,
            'product_videos' => 5,
            'variant_images' => 50,
        ];
    }

    /** @return list<string> */
    public static function productUploadRules(): array
    {
        return ['image', 'mimes:jpeg,jpg,png,webp', 'max:'.self::MAX_IMAGE_KB];
    }

    /** @return list<string> */
    public static function productVideoUploadRules(): array
    {
        return ['file', 'mimes:'.implode(',', self::VIDEO_MIMES), 'max:'.self::MAX_VIDEO_KB];
    }

    public static function isVideoUploadKey(string $key): bool
    {
        return in_array($key, ['videos', 'gallery_videos', 'product_videos'], true);
    }

    /** gallery_images / images accept both images and videos. */
    public static function isMixedMediaUploadKey(string $key): bool
    {
        return in_array($key, ['gallery_images', 'images'], true);
    }

    public static function portfolioUpload(): array
    {
        return [
            'audience' => ['required', 'in:women,men,kids'],
            'portfolio_image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.self::MAX_IMAGE_KB],
        ];
    }
}
