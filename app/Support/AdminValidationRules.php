<?php

namespace App\Support;

use App\Models\Category;
use Illuminate\Validation\Rule;

class AdminValidationRules
{
    /** Earliest value accepted by MySQL TIMESTAMP columns. */
    public const MYSQL_MIN_TIMESTAMP_DATE = '1970-01-01';

    public static function listDateMax(): string
    {
        return now()->format('Y-m-d');
    }

    public const REGEX_PERSON_NAME = '/^[\p{L}\s.\'\-]+$/u';

    public const REGEX_CITY = '/^[\p{L}\s.\'\-]+$/u';

    public const REGEX_PHONE = '/^[0-9]{10}$/';

    public const REGEX_TITLE = '/^[\p{L}\p{N}\s.,\'&()\-]+$/u';

    public const REGEX_TEXT = '/^[\p{L}\p{N}\s.,\'!?&()\-:@#%\/\[\]\n\r]+$/u';

    public const REGEX_CURRENCY = '/^[A-Z]{3,10}$/';

    public const REGEX_GST = '/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/';

    public const REGEX_VEHICLE_NO = '/^[A-Z0-9]{1,20}$/';

    public const REGEX_IFSC = '/^[A-Z]{4}0[A-Z0-9]{6}$/';

    public const REGEX_ACCOUNT_NUMBER = '/^[0-9]{1,20}$/';

    /** Requires local@domain.tld — e.g. name@gmail.com, shop@company.in */
    public const REGEX_EMAIL = '/^(?!\.)(?!.*\.\.)[a-zA-Z0-9._%+\-]+(?<!\.)@(?:[a-zA-Z0-9](?:[a-zA-Z0-9\-]*[a-zA-Z0-9])?\.)+[a-zA-Z]{2,24}$/i';

    /** @var list<string> Allowed domain endings (longest match first when validating). */
    public const ALLOWED_EMAIL_TLDS = [
        'co.in', 'co.uk', 'com.au', 'ac.in', 'edu.in', 'gov.in', 'net.in', 'org.in', 'nic.in', 'res.in', 'gen.in',
        'com', 'in', 'org', 'net', 'edu', 'gov', 'io', 'co', 'uk', 'us', 'au', 'ca', 'de', 'fr', 'info', 'biz',
        'me', 'app', 'dev', 'ai', 'xyz', 'pro', 'int', 'mil',
    ];

    /** @var list<string> */
    public const EMAIL_FIELD_NAMES = [
        'email',
        'business_email',
        'business_mail',
        'support_email',
        'login',
    ];

    public static function emailValidationMessage(): string
    {
        return 'Enter a valid email ID ending with a recognised domain such as .com, .in, or .org (e.g. name@gmail.com).';
    }

    public static function emailFieldHint(): string
    {
        return 'Must end with a valid domain extension such as .com, .in, or .org (e.g. name@gmail.com, shop@company.in).';
    }

    /** HTML5 pattern attribute (no delimiters/flags). */
    public static function htmlEmailPattern(): string
    {
        $tldPattern = implode('|', array_map(
            static fn (string $tld) => str_replace('.', '\\.', preg_quote($tld, '/')),
            self::allowedEmailTldsLongestFirst()
        ));

        return '^(?!\\.)(?!.*\\.\\.)[a-zA-Z0-9._%+\\-]+(?<!\\.)@(?:[a-zA-Z0-9](?:[a-zA-Z0-9\\-]*[a-zA-Z0-9])?\\.)+(?:'.$tldPattern.')$';
    }

    /** @return list<string> */
    public static function allowedEmailTldsLongestFirst(): array
    {
        $tlds = self::ALLOWED_EMAIL_TLDS;
        usort($tlds, static fn (string $a, string $b) => strlen($b) <=> strlen($a));

        return $tlds;
    }

    public static function hasAllowedEmailTld(string $email): bool
    {
        $domain = strtolower(substr(strrchr($email, '@') ?: '', 1));

        if ($domain === '' || ! str_contains($domain, '.')) {
            return false;
        }

        foreach (self::allowedEmailTldsLongestFirst() as $tld) {
            if (str_ends_with($domain, '.'.$tld)) {
                return true;
            }
        }

        return false;
    }

    public static function isValidEmail(?string $value): bool
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return false;
        }

        if (! preg_match(self::REGEX_EMAIL, $value)) {
            return false;
        }

        return self::hasAllowedEmailTld($value);
    }

    public static function isEmailFieldName(string $name): bool
    {
        return in_array($name, self::EMAIL_FIELD_NAMES, true)
            || str_ends_with($name, '_email')
            || $name === 'business_mail';
    }

    public static function looksLikeEmail(string $value): bool
    {
        return str_contains($value, '@');
    }

    /** @param array<string, mixed> $data */
    public static function normalizeEmailFields(array $data): array
    {
        foreach (self::EMAIL_FIELD_NAMES as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $value = trim((string) ($data[$field] ?? ''));
            $data[$field] = $value === '' ? null : $value;
        }

        return $data;
    }

    /** @param array<int, string|\Illuminate\Contracts\Validation\ValidationRule> $additional */
    public static function emailRules(bool $required = true, array $additional = []): array
    {
        $prefix = $required ? ['required'] : ['nullable'];

        return array_merge($prefix, ['string', 'max:255', 'regex:'.self::REGEX_EMAIL], $additional);
    }

    public static function adminLogin(): array
    {
        return [
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    public static function listDateRange(): array
    {
        $minDate = self::MYSQL_MIN_TIMESTAMP_DATE;
        $maxDate = self::listDateMax();

        return [
            'from' => ['nullable', 'date', 'after_or_equal:'.$minDate, 'before_or_equal:'.$maxDate],
            'to' => ['nullable', 'date', 'after_or_equal:'.$minDate, 'before_or_equal:'.$maxDate, 'after_or_equal:from'],
            'registered_on' => ['nullable', 'date', 'after_or_equal:'.$minDate, 'before_or_equal:'.$maxDate],
        ];
    }

    public static function vendor(?int $vendorId = null): array
    {
        $uniqueMobile = 'unique:vendors,mobile';
        $uniqueEmail = 'unique:vendors,email';

        if ($vendorId) {
            $uniqueMobile .= ','.$vendorId;
            $uniqueEmail .= ','.$vendorId;
        }

        return [
            'shop_name' => ['nullable', 'string', 'max:100', 'regex:'.self::REGEX_TITLE],
            'brand_name' => ['required', 'string', 'max:100', 'regex:'.self::REGEX_TITLE],
            'owner_name' => ['required', 'string', 'max:100', 'regex:'.self::REGEX_PERSON_NAME],
            'mobile' => ['required', 'string', 'regex:'.self::REGEX_PHONE, $uniqueMobile],
            'email' => self::emailRules(true, [$uniqueEmail]),
            'business_mobile' => ['nullable', 'string', 'regex:'.self::REGEX_PHONE],
            'business_email' => self::emailRules(false),
            'gst_number' => ['nullable', 'string', 'size:15', 'regex:'.self::REGEX_GST],
            'address' => ['nullable', 'string', 'max:500', 'regex:'.self::REGEX_TEXT],
            'country' => ['nullable', 'string', 'max:100', 'regex:'.self::REGEX_CITY],
            'state' => ['nullable', 'string', 'max:100', 'regex:'.self::REGEX_CITY],
            'city' => ['nullable', 'string', 'max:100', 'regex:'.self::REGEX_CITY],
            'country_id' => ['nullable'],
            'country_other' => ['nullable', 'required_if:country_id,other', 'string', 'max:100', 'regex:'.self::REGEX_CITY],
            'state_id' => ['nullable'],
            'state_other' => ['nullable', 'required_if:state_id,other', 'required_if:country_id,other', 'string', 'max:100', 'regex:'.self::REGEX_CITY],
            'city_id' => ['nullable'],
            'city_other' => ['nullable', 'required_if:city_id,other', 'required_if:state_id,other', 'required_if:country_id,other', 'string', 'max:100', 'regex:'.self::REGEX_CITY],
            'pincode' => ['nullable', 'string', 'max:10'],
            'account_name' => ['nullable', 'string', 'max:255', 'regex:'.self::REGEX_PERSON_NAME],
            'account_number' => ['nullable', 'string', 'max:20', 'regex:'.self::REGEX_ACCOUNT_NUMBER],
            'ifsc_code' => ['nullable', 'string', 'size:11', 'regex:'.self::REGEX_IFSC],
            'bank_name' => ['nullable', 'string', 'max:255', 'regex:'.self::REGEX_TITLE],
            'account_type' => ['nullable', 'in:savings,current'],
            'status' => ['required', 'in:pending,active,suspended,rejected'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'orders_completed' => ['nullable', 'integer', 'min:0'],
            'earnings' => ['nullable', 'numeric', 'min:0'],
            'commission' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'audience_category_ids' => ['required', 'array', 'min:1'],
            'audience_category_ids.*' => ['integer', 'exists:categories,id'],
            'service_category_ids' => ['required', 'array', 'min:1'],
            'service_category_ids.*' => ['integer', 'exists:categories,id'],
            'profile_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'shop_logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'shop_images' => ['nullable', 'array', 'max:12'],
            'shop_images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'remove_shop_image_ids' => ['nullable', 'array'],
            'remove_shop_image_ids.*' => ['integer', 'exists:vendor_shop_images,id'],
            'aadhar_front' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'aadhar_back' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'pan_card' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ];
    }

    public static function driver(?int $driverId = null): array
    {
        $uniqueMobile = 'unique:drivers,mobile';

        if ($driverId) {
            $uniqueMobile .= ','.$driverId;
        }

        return [
            'name' => ['required', 'string', 'max:100', 'regex:'.self::REGEX_PERSON_NAME],
            'mobile' => ['required', 'string', 'regex:'.self::REGEX_PHONE, $uniqueMobile],
            'email' => self::emailRules(false),
            'city' => ['nullable', 'string', 'max:100', 'regex:'.self::REGEX_CITY],
            'vehicle_no' => ['nullable', 'string', 'max:20', 'regex:'.self::REGEX_VEHICLE_NO],
            'account_name' => ['nullable', 'string', 'max:255', 'regex:'.self::REGEX_PERSON_NAME],
            'account_number' => ['nullable', 'string', 'max:20', 'regex:'.self::REGEX_ACCOUNT_NUMBER],
            'ifsc_code' => ['nullable', 'string', 'size:11', 'regex:'.self::REGEX_IFSC],
            'bank_name' => ['nullable', 'string', 'max:255', 'regex:'.self::REGEX_TITLE],
            'account_type' => ['nullable', 'in:savings,current'],
            'status' => ['required', 'in:pending,active,suspended,rejected'],
            'is_verified' => ['nullable', 'boolean'],
            'profile_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'aadhar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'aadhar_front' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'aadhar_back' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'driving_licence' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ];
    }

    public static function customer(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'regex:'.self::REGEX_PERSON_NAME],
            'mobile' => ['required', 'string', 'regex:'.self::REGEX_PHONE],
            'email' => self::emailRules(false),
            'city' => ['nullable', 'string', 'max:100', 'regex:'.self::REGEX_CITY],
            'status' => ['required', 'in:active,suspended,blocked'],
            'is_verified' => ['nullable', 'boolean'],
            'registered_at' => ['nullable', 'date', 'after_or_equal:'.self::MYSQL_MIN_TIMESTAMP_DATE, 'before_or_equal:today'],
            'profile_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ];
    }

    public static function order(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'driver_id' => ['nullable', 'exists:drivers,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'order_type' => ['required', 'in:sale,rental'],
            'item_title' => ['nullable', 'string', 'max:255', 'regex:'.self::REGEX_TITLE],
            'item_description' => ['nullable', 'string', 'max:2000', 'regex:'.self::REGEX_TEXT],
            'size' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:100', 'regex:'.self::REGEX_TITLE],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'event_date' => ['nullable', 'date'],
            'rental_start_date' => ['nullable', 'date'],
            'rental_end_date' => ['nullable', 'date', 'after_or_equal:rental_start_date'],
            'return_due_date' => ['nullable', 'date'],
            'delivery_address' => ['nullable', 'string', 'max:1000', 'regex:'.self::REGEX_TEXT],
            'pickup_address' => ['nullable', 'string', 'max:1000', 'regex:'.self::REGEX_TEXT],
            'city' => ['nullable', 'string', 'max:100', 'regex:'.self::REGEX_CITY],
            'pincode' => ['nullable', 'string', 'max:10'],
            'amount' => ['required', 'numeric', 'min:0'],
            'security_deposit' => ['nullable', 'numeric', 'min:0'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'customer_notes' => ['nullable', 'string', 'max:2000', 'regex:'.self::REGEX_TEXT],
            'admin_notes' => ['nullable', 'string', 'max:2000', 'regex:'.self::REGEX_TEXT],
            'damage_note' => ['nullable', 'string', 'max:255'],
            'damage_deduct_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'measure_height_cm' => ['nullable', 'integer', 'min:50', 'max:250'],
            'measure_chest_cm' => ['nullable', 'integer', 'min:50', 'max:200'],
            'measure_waist_cm' => ['nullable', 'integer', 'min:40', 'max:200'],
            'billing_address' => ['nullable', 'string', 'max:1000', 'regex:'.self::REGEX_TEXT],
            'item_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'reference_images' => ['nullable', 'array'],
            'reference_images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'payment_status' => ['required', 'in:pending,success,failed,refunded'],
            'status' => ['required', 'in:new,pending_acceptance,accepted,in_progress,in_transit,delivered,cancelled,refunded'],
        ];
    }

    public static function refundStore(): array
    {
        return [
            'order_id' => ['required', 'exists:orders,id'],
            'customer_id' => ['required', 'exists:customers,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:500', 'regex:'.self::REGEX_TEXT],
            'status' => ['required', 'in:requested,under_review,approved,rejected,processed'],
        ];
    }

    public static function refundUpdate(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:500', 'regex:'.self::REGEX_TEXT],
            'status' => ['required', 'in:requested,under_review,approved,rejected,processed'],
        ];
    }

    public static function disputeStore(): array
    {
        return [
            'order_id' => ['required', 'exists:orders,id'],
            'raised_by' => ['required', 'in:customer,vendor'],
            'subject' => ['required', 'string', 'max:255', 'regex:'.self::REGEX_TITLE],
            'status' => ['required', 'in:raised,under_review,resolved,closed'],
        ];
    }

    public static function disputeUpdate(): array
    {
        return [
            'raised_by' => ['required', 'in:customer,vendor'],
            'subject' => ['required', 'string', 'max:255', 'regex:'.self::REGEX_TITLE],
            'status' => ['required', 'in:raised,under_review,resolved,closed'],
            'resolution_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public static function banner(): array
    {
        return [
            'audience' => ['required', 'in:customer,vendor,driver'],
            'title' => ['required', 'string', 'max:255', 'regex:'.self::REGEX_TITLE],
            'subtitle' => ['nullable', 'string', 'max:255', 'regex:'.self::REGEX_TITLE],
            'redirect_url' => ['nullable', 'url', 'max:500'],
            'starts_at' => ['nullable', 'date', 'after_or_equal:'.self::MYSQL_MIN_TIMESTAMP_DATE],
            'ends_at' => ['nullable', 'date', 'after_or_equal:'.self::MYSQL_MIN_TIMESTAMP_DATE, 'after_or_equal:starts_at'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:4096'],
        ];
    }

    public static function category(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'regex:'.self::REGEX_TITLE],
            'type' => ['required', 'in:main,service,sub'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ];
    }

    public static function faq(): array
    {
        return [
            'audience' => ['required', 'in:user,vendor,driver'],
            'question' => ['required', 'string', 'max:500', 'regex:'.self::REGEX_TEXT],
            'answer' => ['required', 'string', 'max:50000', 'regex:'.self::REGEX_TEXT],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    public static function role(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'regex:'.self::REGEX_TITLE],
            'slug' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/'],
            'description' => ['nullable', 'string', 'max:500', 'regex:'.self::REGEX_TEXT],
            'is_active' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['array'],
            'permissions.*.can_view' => ['nullable', 'boolean'],
            'permissions.*.can_create' => ['nullable', 'boolean'],
            'permissions.*.can_edit' => ['nullable', 'boolean'],
            'permissions.*.can_delete' => ['nullable', 'boolean'],
            'permissions.*.can_export' => ['nullable', 'boolean'],
        ];
    }

    public static function adminUser(?int $adminId = null): array
    {
        $uniqueUsername = 'unique:admins,username';
        $uniqueEmail = 'unique:admins,email';

        if ($adminId) {
            $uniqueUsername .= ','.$adminId;
            $uniqueEmail .= ','.$adminId;
        }

        return [
            'role_id' => ['required', 'exists:roles,id'],
            'name' => ['required', 'string', 'max:255', 'regex:'.self::REGEX_TITLE],
            'username' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9._-]+$/', $uniqueUsername],
            'email' => self::emailRules(true, [$uniqueEmail]),
            'password' => [$adminId ? 'nullable' : 'required', 'string', 'min:8', 'max:128'],
            'status' => ['required', 'in:active,inactive,suspended'],
            'city' => ['nullable', 'string', 'max:100', 'regex:'.self::REGEX_CITY],
        ];
    }

    public static function notificationStore(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', 'regex:'.self::REGEX_TITLE],
            'message' => ['required', 'string', 'max:2000', 'regex:'.self::REGEX_TEXT],
            'channel' => ['required', 'in:push,email,sms'],
            'audience' => ['required', 'in:all_customers,all_vendors,customers,vendors'],
        ];
    }

    public static function settingsBranding(): array
    {
        return [
            'platform_name' => ['required', 'string', 'max:255', 'regex:'.self::REGEX_TITLE],
            'admin_logo' => ['nullable', 'image', 'max:2048'],
            'vendor_logo' => ['nullable', 'image', 'max:2048'],
            'website_logo' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public static function settingsTheme(): array
    {
        return [
            'theme_primary_color' => ['required', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'theme_primary_hover' => ['required', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'theme_sidebar_bg' => ['required', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'theme_sidebar_hover' => ['required', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'theme_sidebar_text' => ['required', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'theme_topbar_bg' => ['required', 'regex:/^#([A-Fa-f0-9]{6})$/'],
        ];
    }

    public static function settingsContact(): array
    {
        return [
            'support_email' => self::emailRules(true),
            'support_phone' => ['nullable', 'string', 'regex:'.self::REGEX_PHONE],
            'contact_address' => ['nullable', 'string', 'max:500', 'regex:'.self::REGEX_TEXT],
        ];
    }

    public static function settingsLegal(): array
    {
        $legalText = ['nullable', 'string', 'max:50000'];

        return [
            'terms_conditions_user' => $legalText,
            'terms_conditions_vendor' => $legalText,
            'terms_conditions_driver' => $legalText,
            'privacy_policy_user' => $legalText,
            'privacy_policy_vendor' => $legalText,
            'privacy_policy_driver' => $legalText,
            'about_us' => $legalText,
            'help_support' => $legalText,
        ];
    }

    public static function settingsLegalAudience(string $audience): array
    {
        $legalText = ['nullable', 'string', 'max:50000'];

        return match ($audience) {
            'vendor' => [
                'terms_conditions_vendor' => $legalText,
                'privacy_policy_vendor' => $legalText,
            ],
            'driver' => [
                'terms_conditions_driver' => $legalText,
                'privacy_policy_driver' => $legalText,
            ],
            'general' => [
                'about_us' => $legalText,
                'help_support' => $legalText,
            ],
            default => [
                'terms_conditions_user' => $legalText,
                'privacy_policy_user' => $legalText,
            ],
        };
    }

    public static function settingsFeatures(): array
    {
        return [
            'currency' => ['required', 'string', 'regex:'.self::REGEX_CURRENCY],
        ];
    }

    public static function settingsCommission(): array
    {
        return [
            'global_commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public static function vendorSuspend(): array
    {
        return [
            'suspension_reason' => ['required', 'string', 'min:5', 'max:1000', 'regex:'.self::REGEX_TEXT],
        ];
    }

    public static function accountRejection(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'min:5', 'max:500', 'regex:'.self::REGEX_TEXT],
        ];
    }

    public static function portfolioItem(bool $creating = false): array
    {
        $priceRule = $creating ? 'required' : 'nullable';

        return [
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            ...\App\Support\SubcategoryCatalog::subcategoryIdRules(true),
            'title' => ['required', 'string', 'max:255', 'regex:'.self::REGEX_TITLE],
            'description' => ['nullable', 'string', 'max:5000', 'regex:'.self::REGEX_TEXT],
            'price_per_day' => [$priceRule, 'numeric', 'min:0', 'max:9999999'],
            'advance_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'audience' => ['required', 'in:women,men,kids'],
            'status' => ['required', 'in:pending,approved,rejected'],
            'rejection_reason' => ['nullable', 'string', 'max:500', 'regex:'.self::REGEX_TEXT],
            'image' => [$creating ? 'required' : 'nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.VendorValidationRules::MAX_IMAGE_KB],
            'gallery_images' => ['nullable', 'array', 'max:10'],
            'gallery_images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:'.VendorValidationRules::MAX_IMAGE_KB],
            'variants' => ['nullable', 'array', 'max:50'],
            'variants.*.size' => ['required_with:variants', 'string', 'max:50', 'regex:'.self::REGEX_TITLE],
            'variants.*.color' => ['required_with:variants', 'string', 'max:100', 'regex:'.self::REGEX_TITLE],
            'variants.*.price' => ['required_with:variants', 'numeric', 'min:0', 'max:9999999'],
            'variant_images' => ['nullable', 'array', 'max:50'],
            'variant_images.*' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.VendorValidationRules::MAX_IMAGE_KB],
            'damage_deductions' => ['nullable', 'array', 'max:20'],
            'damage_deductions.*.damage_type' => ['required_with:damage_deductions', 'string', 'max:100', 'regex:'.self::REGEX_TITLE],
            'damage_deductions.*.percent' => ['required_with:damage_deductions', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public static function settingsRefundRules(): array
    {
        $policyText = ['nullable', 'string', 'max:50000'];
        $days = ['required', 'integer', 'min:0', 'max:365'];
        $percent = ['required', 'numeric', 'min:0', 'max:100'];
        $amount = ['required', 'numeric', 'min:0', 'max:999999'];

        return [
            'refund_policy_user' => $policyText,
            'return_policy_user' => $policyText,
            'refund_rental_cancel_days' => $days,
            'refund_rental_late_fee_per_day' => $amount,
            'refund_rental_deposit_days' => $days,
            'refund_sale_window_days' => $days,
            'refund_sale_return_days' => $days,
            'refund_sale_restocking_percent' => $percent,
        ];
    }

    public static function settingsDamageDeductionRules(): array
    {
        $percent = ['required', 'numeric', 'min:0', 'max:100'];

        return [
            'tab' => ['nullable', 'string', Rule::in(['catalog', 'service'])],
            'damage_deduction_rules' => ['nullable', 'array'],
            'damage_deduction_rules.*.category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('type', Category::TYPE_MAIN)),
            ],
            'damage_deduction_rules.*.subcategory_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('type', Category::TYPE_SUB)),
            ],
            'damage_deduction_rules.*.max_percent' => $percent,
            'service_damage_deduction_rules' => ['nullable', 'array'],
            'service_damage_deduction_rules.*.category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('type', Category::TYPE_MAIN)),
            ],
            'service_damage_deduction_rules.*.subcategory_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('type', Category::TYPE_SUB)),
            ],
            'service_damage_deduction_rules.*.service_category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('type', Category::TYPE_SERVICE)),
            ],
            'service_damage_deduction_rules.*.max_percent' => $percent,
        ];
    }

    public static function messages(): array
    {
        return [
            'from.date' => 'Enter a valid from date.',
            'from.after_or_equal' => 'From date must be on or after Jan 1, 1970.',
            'from.before_or_equal' => 'From date cannot be in the future.',
            'to.date' => 'Enter a valid to date.',
            'to.after_or_equal' => 'To date must be on or after Jan 1, 1970 and not before the from date.',
            'to.before_or_equal' => 'To date cannot be in the future.',
            'registered_on.after_or_equal' => 'Registered date must be on or after Jan 1, 1970.',
            'registered_on.before_or_equal' => 'Registered date cannot be in the future.',
            'starts_at.date' => 'Enter a valid start date.',
            'starts_at.after_or_equal' => 'Start date must be on or after Jan 1, 1970.',
            'ends_at.date' => 'Enter a valid end date.',
            'ends_at.after_or_equal' => 'End date must be on or after Jan 1, 1970 and not before the start date.',
            'amount.min' => 'Amount cannot be negative.',
            'rating.min' => 'Rating cannot be negative.',
            'rating.max' => 'Rating cannot exceed 5.',
            'orders_completed.min' => 'Orders completed cannot be negative.',
            'earnings.min' => 'Earnings cannot be negative.',
            'global_commission_percent.min' => 'Commission cannot be negative.',
            'global_commission_percent.max' => 'Commission cannot exceed 100%.',
            'refund_rental_cancel_days.min' => 'Cancellation notice cannot be negative.',
            'refund_rental_cancel_days.max' => 'Cancellation notice cannot exceed 365 days.',
            'refund_rental_late_fee_per_day.min' => 'Late return fee cannot be negative.',
            'damage_deduction_rules.min' => 'Configure at least one damage deduction rule.',
            'damage_deduction_rules.*.category_id.required' => 'Category is required.',
            'damage_deduction_rules.*.category_id.exists' => 'Select a valid category.',
            'damage_deduction_rules.*.subcategory_id.exists' => 'Select a valid sub-category.',
            'damage_deduction_rules.*.max_percent.min' => 'Damage deduction cannot be negative.',
            'damage_deduction_rules.*.max_percent.max' => 'Damage deduction cannot exceed 100%.',
            'service_damage_deduction_rules.min' => 'Configure at least one damage deduction rule.',
            'service_damage_deduction_rules.*.category_id.required' => 'Category is required.',
            'service_damage_deduction_rules.*.category_id.exists' => 'Select a valid category.',
            'service_damage_deduction_rules.*.subcategory_id.exists' => 'Select a valid sub-category.',
            'service_damage_deduction_rules.*.service_category_id.required' => 'Service category is required.',
            'service_damage_deduction_rules.*.service_category_id.exists' => 'Select a valid service category.',
            'service_damage_deduction_rules.*.max_percent.min' => 'Damage deduction cannot be negative.',
            'service_damage_deduction_rules.*.max_percent.max' => 'Damage deduction cannot exceed 100%.',
            'suspension_reason.required' => 'A suspension reason is required.',
            'suspension_reason.min' => 'Suspension reason must be at least 10 characters.',
            'refund_rental_deposit_days.min' => 'Deposit refund days cannot be negative.',
            'refund_sale_window_days.min' => 'Refund window cannot be negative.',
            'refund_sale_return_days.min' => 'Return window cannot be negative.',
            'refund_sale_restocking_percent.min' => 'Restocking fee cannot be negative.',
            'refund_sale_restocking_percent.max' => 'Restocking fee cannot exceed 100%.',
            'name.regex' => 'Name may only contain letters, spaces, dots, and hyphens.',
            'owner_name.regex' => 'Owner name may only contain letters, spaces, dots, and hyphens.',
            'brand_name.regex' => 'Brand name contains invalid characters.',
            'mobile.regex' => 'Mobile no must be exactly 10 digits.',
            'mobile.digits' => 'Mobile no must be exactly 10 digits.',
            'business_mobile.regex' => 'Business mobile no must be exactly 10 digits.',
            'business_mobile.digits' => 'Business mobile no must be exactly 10 digits.',
            'gst_number.regex' => 'Enter a valid 15-character GSTIN.',
            'gst_number.size' => 'GST number must be exactly 15 characters.',
            'vehicle_no.regex' => 'Vehicle number may only contain letters and numbers (max 20).',
            'support_phone.regex' => 'Phone no must be exactly 10 digits.',
            'support_phone.digits' => 'Phone no must be exactly 10 digits.',
            'city.regex' => 'City may only contain letters, spaces, dots, and hyphens.',
            'title.regex' => 'This field contains invalid characters.',
            'subtitle.regex' => 'Subtitle contains invalid characters.',
            'subject.regex' => 'Subject contains invalid characters.',
            'platform_name.regex' => 'Platform name contains invalid characters.',
            'categories_text.regex' => 'Categories may only use letters, numbers, commas, and spaces.',
            'category_ids.array' => 'Select at least one category for this sub-admin.',
            'audience_category_ids.required' => 'Select at least one category (Men, Women, or Kids).',
            'audience_category_ids.min' => 'Select at least one category (Men, Women, or Kids).',
            'service_category_ids.required' => 'Select at least one service category.',
            'service_category_ids.min' => 'Select at least one service category.',
            'registered_at.after_or_equal' => 'Registration date must be on or after Jan 1, 1970.',
            'registered_at.before_or_equal' => 'Registration date cannot be in the future.',
            'city.required' => 'Select a city for this sub-admin.',
            'account_type.in' => 'Account type must be savings or current.',
            'account_name.regex' => 'Account holder name may only contain letters, spaces, dots, and hyphens.',
            'account_number.regex' => 'Account number must be 1–20 digits only.',
            'account_number.max' => 'Account number must not exceed 20 digits.',
            'ifsc_code.regex' => 'Enter a valid 11-character IFSC code.',
            'ifsc_code.size' => 'IFSC code must be exactly 11 characters.',
            'bank_name.regex' => 'Bank name contains invalid characters.',
            'currency.regex' => 'Currency must be 3–10 uppercase letters (e.g. INR).',
            'reason.regex' => 'Reason contains invalid characters.',
            'message.regex' => 'Message contains invalid characters.',
            'profile_image.max' => 'Image is too large. Maximum size is 4 MB.',
            'profile_image.uploaded' => 'Image is too large. Maximum size is 4 MB.',
            '*.regex' => 'This field contains invalid characters.',
            'email.regex' => 'Enter a valid email ID ending with .com, .in, .org, or another recognised domain (e.g. name@gmail.com).',
            'business_email.regex' => 'Enter a valid business email ID ending with .com, .in, .org, or another recognised domain.',
            'business_mail.regex' => 'Enter a valid business email ID ending with .com, .in, .org, or another recognised domain.',
            'support_email.regex' => 'Enter a valid support email ID ending with .com, .in, .org, or another recognised domain.',
            'login.regex' => 'Enter a valid email ID ending with .com, .in, .org, or another recognised domain.',
        ];
    }

    public static function attributes(): array
    {
        return [
            'from' => 'from date',
            'to' => 'to date',
            'starts_at' => 'start date',
            'ends_at' => 'end date',
            'brand_name' => 'brand name',
            'owner_name' => 'owner name',
            'customer_id' => 'customer',
            'vendor_id' => 'vendor',
            'category_id' => 'category',
            'subcategory_id' => 'sub-category',
            'order_id' => 'order',
            'raised_by' => 'raised by',
            'global_commission_percent' => 'commission',
            'refund_rental_cancel_days' => 'rental cancellation days',
            'refund_rental_late_fee_per_day' => 'late return fee',
            'damage_deduction_rules.*.category_id' => 'category',
            'damage_deduction_rules.*.subcategory_id' => 'sub-category',
            'damage_deduction_rules.*.max_percent' => 'max damage deduction',
            'service_damage_deduction_rules.*.service_category_id' => 'service category',
            'service_damage_deduction_rules.*.category_id' => 'category',
            'service_damage_deduction_rules.*.subcategory_id' => 'sub-category',
            'service_damage_deduction_rules.*.max_percent' => 'max damage deduction',
            'suspension_reason' => 'suspension reason',
            'rejection_reason' => 'rejection reason',
            'refund_rental_deposit_days' => 'security deposit refund days',
            'refund_sale_window_days' => 'sale refund window',
            'refund_sale_return_days' => 'sale return window',
            'refund_sale_restocking_percent' => 'restocking fee',
            'name' => 'full name',
            'mobile' => 'mobile no',
            'email' => 'email ID',
            'business_mobile' => 'business mobile no',
            'business_email' => 'business email ID',
            'support_email' => 'support email ID',
            'login' => 'email ID or username',
            'image' => 'banner image',
            'audience_category_ids' => 'categories',
            'service_category_ids' => 'service categories',
        ];
    }

    /** Default live-input restriction key for a field name. */
    public static function defaultRestrict(string $name, ?string $type = 'text'): ?string
    {
        return match ($name) {
            'owner_name' => 'person-name',
            'account_name' => 'person-name',
            'account_number' => 'account-number',
            'ifsc_code' => 'ifsc',
            'bank_name' => 'title',
            'name' => 'person-name',
            'mobile', 'support_phone' => 'phone',
            'city' => 'city',
            'brand_name', 'title', 'subtitle', 'subject', 'platform_name' => 'title',
            'email', 'business_email', 'business_mail', 'support_email' => 'email',
            'redirect_url' => 'url',
            'currency' => 'currency',
            'gst_number' => 'gst',
            'vehicle_no' => 'vehicle-no',
            'reason', 'message', 'contact_address', 'suspension_reason' => 'text',
            'terms_conditions_user', 'terms_conditions_vendor', 'terms_conditions_driver',
            'privacy_policy_user', 'privacy_policy_vendor', 'privacy_policy_driver',
            'about_us', 'help_support',
            'refund_policy_user', 'return_policy_user' => 'text',
            'refund_rental_cancel_days', 'refund_rental_deposit_days',
            'refund_sale_window_days', 'refund_sale_return_days' => 'integer',
            'refund_rental_late_fee_per_day', 'damage_deduction_rules.*.max_percent',
            'refund_sale_restocking_percent' => 'decimal',
            'amount', 'earnings', 'global_commission_percent', 'rating' => 'decimal',
            'orders_completed' => 'integer',
            default => ($type === 'email' ? 'email' : ($type === 'url' ? 'url' : null)),
        };
    }

    public static function isMysqlTimestampDate(?string $value): bool
    {
        if ($value === null || trim($value) === '') {
            return false;
        }

        $timestamp = strtotime($value);

        if ($timestamp === false) {
            return false;
        }

        return $timestamp >= strtotime(self::MYSQL_MIN_TIMESTAMP_DATE.' 00:00:00')
            && $timestamp <= strtotime('today 23:59:59');
    }

    public static function normalizeMysqlTimestampDate(?string $value): ?string
    {
        if (! self::isMysqlTimestampDate($value)) {
            return null;
        }

        return date('Y-m-d', strtotime((string) $value));
    }
}
