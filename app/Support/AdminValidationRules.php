<?php

namespace App\Support;

class AdminValidationRules
{
    public const REGEX_PERSON_NAME = '/^[\p{L}\s.\'\-]+$/u';

    public const REGEX_CITY = '/^[\p{L}\s.\'\-]+$/u';

    public const REGEX_PHONE = '/^[0-9]{10,15}$/';

    public const REGEX_TITLE = '/^[\p{L}\p{N}\s.,\'&()\-]+$/u';

    public const REGEX_TEXT = '/^[\p{L}\p{N}\s.,\'!?&()\-:@#%\/\[\]\n\r]+$/u';

    public const REGEX_CURRENCY = '/^[A-Z]{3,10}$/';

    public const REGEX_COMMA_LIST = '/^[\p{L}\p{N}\s,.\-]*$/u';

    public static function listDateRange(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
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
            'shop_name' => ['nullable', 'string', 'max:255', 'regex:'.self::REGEX_TITLE],
            'brand_name' => ['required', 'string', 'max:255', 'regex:'.self::REGEX_TITLE],
            'owner_name' => ['required', 'string', 'max:255', 'regex:'.self::REGEX_PERSON_NAME],
            'mobile' => ['required', 'string', 'regex:'.self::REGEX_PHONE, $uniqueMobile],
            'email' => ['required', 'email', 'max:255', $uniqueEmail],
            'service_types' => ['nullable', 'string', 'max:500', 'regex:'.self::REGEX_TEXT],
            'business_mobile' => ['nullable', 'string', 'regex:'.self::REGEX_PHONE],
            'business_email' => ['nullable', 'email', 'max:255'],
            'gst_number' => ['nullable', 'string', 'max:15'],
            'address' => ['nullable', 'string', 'max:500', 'regex:'.self::REGEX_TEXT],
            'country' => ['nullable', 'string', 'max:100', 'regex:'.self::REGEX_CITY],
            'state' => ['nullable', 'string', 'max:100', 'regex:'.self::REGEX_CITY],
            'city' => ['nullable', 'string', 'max:100', 'regex:'.self::REGEX_CITY],
            'pincode' => ['nullable', 'string', 'max:10'],
            'account_name' => ['nullable', 'string', 'max:255', 'regex:'.self::REGEX_PERSON_NAME],
            'account_number' => ['nullable', 'string', 'max:20'],
            'ifsc_code' => ['nullable', 'string', 'max:11'],
            'bank_name' => ['nullable', 'string', 'max:255', 'regex:'.self::REGEX_TITLE],
            'account_type' => ['nullable', 'in:savings,current'],
            'status' => ['required', 'in:pending,active,suspended,rejected'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'orders_completed' => ['nullable', 'integer', 'min:0'],
            'earnings' => ['nullable', 'numeric', 'min:0'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'profile_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'shop_logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
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
            'name' => ['required', 'string', 'max:255', 'regex:'.self::REGEX_PERSON_NAME],
            'mobile' => ['required', 'string', 'regex:'.self::REGEX_PHONE, $uniqueMobile],
            'email' => ['nullable', 'email', 'max:255'],
            'city' => ['nullable', 'string', 'max:100', 'regex:'.self::REGEX_CITY],
            'vehicle_no' => ['nullable', 'string', 'max:20'],
            'account_name' => ['nullable', 'string', 'max:255', 'regex:'.self::REGEX_PERSON_NAME],
            'account_number' => ['nullable', 'string', 'max:20'],
            'ifsc_code' => ['nullable', 'string', 'max:11'],
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
            'name' => ['required', 'string', 'max:255', 'regex:'.self::REGEX_PERSON_NAME],
            'mobile' => ['required', 'string', 'regex:'.self::REGEX_PHONE],
            'email' => ['nullable', 'email', 'max:255'],
            'city' => ['nullable', 'string', 'max:100', 'regex:'.self::REGEX_CITY],
            'status' => ['required', 'in:active,suspended,blocked'],
            'is_verified' => ['nullable', 'boolean'],
            'registered_at' => ['nullable', 'date'],
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
        ];
    }

    public static function banner(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', 'regex:'.self::REGEX_TITLE],
            'subtitle' => ['nullable', 'string', 'max:255', 'regex:'.self::REGEX_TITLE],
            'cta_label' => ['nullable', 'string', 'max:100', 'regex:'.self::REGEX_TITLE],
            'redirect_url' => ['nullable', 'url', 'max:500'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:4096'],
        ];
    }

    public static function category(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'regex:'.self::REGEX_TITLE],
            'type' => ['required', 'in:main,service'],
            'parent_id' => ['nullable', 'exists:categories,id'],
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
            'email' => ['required', 'email', 'max:255', $uniqueEmail],
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
            'support_email' => ['required', 'email', 'max:255'],
            'support_phone' => ['nullable', 'string', 'regex:'.self::REGEX_PHONE],
            'contact_address' => ['nullable', 'string', 'max:500', 'regex:'.self::REGEX_TEXT],
        ];
    }

    public static function settingsLegal(): array
    {
        $legalText = ['nullable', 'string', 'max:50000', 'regex:'.self::REGEX_TEXT];

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

    public static function messages(): array
    {
        return [
            'from.date' => 'Enter a valid from date.',
            'to.date' => 'Enter a valid to date.',
            'to.after_or_equal' => 'To date must be the same as or after the from date.',
            'starts_at.date' => 'Enter a valid start date.',
            'ends_at.date' => 'Enter a valid end date.',
            'ends_at.after_or_equal' => 'End date must be the same as or after the start date.',
            'amount.min' => 'Amount cannot be negative.',
            'rating.min' => 'Rating cannot be negative.',
            'rating.max' => 'Rating cannot exceed 5.',
            'orders_completed.min' => 'Orders completed cannot be negative.',
            'earnings.min' => 'Earnings cannot be negative.',
            'global_commission_percent.min' => 'Commission cannot be negative.',
            'global_commission_percent.max' => 'Commission cannot exceed 100%.',
            'name.regex' => 'Name may only contain letters, spaces, dots, and hyphens.',
            'owner_name.regex' => 'Owner name may only contain letters, spaces, dots, and hyphens.',
            'brand_name.regex' => 'Brand name contains invalid characters.',
            'mobile.regex' => 'Mobile must be 10–15 digits only.',
            'support_phone.regex' => 'Phone must be 10–15 digits only.',
            'city.regex' => 'City may only contain letters, spaces, dots, and hyphens.',
            'title.regex' => 'This field contains invalid characters.',
            'subtitle.regex' => 'Subtitle contains invalid characters.',
            'cta_label.regex' => 'CTA label contains invalid characters.',
            'subject.regex' => 'Subject contains invalid characters.',
            'platform_name.regex' => 'Platform name contains invalid characters.',
            'categories_text.regex' => 'Categories may only use letters, numbers, commas, and spaces.',
            'category_ids.array' => 'Select at least one category for this sub-admin.',
            'city.required' => 'Select a city for this sub-admin.',
            'account_type.in' => 'Account type must be savings or current.',
            'currency.regex' => 'Currency must be 3–10 uppercase letters (e.g. INR).',
            'reason.regex' => 'Reason contains invalid characters.',
            'message.regex' => 'Message contains invalid characters.',
            '*.regex' => 'This field contains invalid characters.',
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
            'order_id' => 'order',
            'raised_by' => 'raised by',
            'global_commission_percent' => 'commission',
            'name' => 'full name',
            'mobile' => 'mobile',
            'image' => 'banner image',
        ];
    }

    /** Default live-input restriction key for a field name. */
    public static function defaultRestrict(string $name, ?string $type = 'text'): ?string
    {
        return match ($name) {
            'owner_name' => 'person-name',
            'name' => 'person-name',
            'mobile', 'support_phone' => 'phone',
            'city' => 'city',
            'brand_name', 'title', 'subtitle', 'cta_label', 'subject', 'platform_name' => 'title',
            'email', 'support_email' => 'email',
            'redirect_url' => 'url',
            'currency' => 'currency',
            'categories_text' => 'comma-list',
            'reason', 'message', 'contact_address',
            'terms_conditions_user', 'terms_conditions_vendor', 'terms_conditions_driver',
            'privacy_policy_user', 'privacy_policy_vendor', 'privacy_policy_driver',
            'about_us', 'help_support' => 'text',
            'amount', 'earnings', 'global_commission_percent', 'rating' => 'decimal',
            'orders_completed' => 'integer',
            default => ($type === 'email' ? 'email' : ($type === 'url' ? 'url' : null)),
        };
    }
}
