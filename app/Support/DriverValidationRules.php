<?php

namespace App\Support;

class DriverValidationRules
{
    /** @return array<string, array<int, mixed>> */
    public static function deliveryReject(): array
    {
        return [
            'reason' => ['required', 'string', 'min:3', 'max:500', 'regex:'.AdminValidationRules::REGEX_TEXT],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    public static function deliveryReschedule(): array
    {
        return [
            'scheduled_date' => ['required', 'date', 'after_or_equal:today'],
            'reason' => ['nullable', 'string', 'max:500', 'regex:'.AdminValidationRules::REGEX_TEXT],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    public static function deliveryComplete(bool $requireDeliveryOtp = true): array
    {
        return [
            'item_id' => ['nullable', 'integer', 'min:1'],
            'delivery_otp' => $requireDeliveryOtp
                ? ['required', 'digits:4']
                : ['nullable', 'digits:4'],
            'delivery_image' => MediaUploadSupport::imageRules(4096),
        ];
    }
}
