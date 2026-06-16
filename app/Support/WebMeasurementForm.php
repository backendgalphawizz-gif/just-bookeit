<?php

namespace App\Support;

class WebMeasurementForm
{
    /** @return array<string, list<string>> */
    public static function sections(): array
    {
        return [
            'Upper Body' => ['Blouse length', 'Shoulder', 'Arm hole', 'Chest', 'Waist', 'Dot point'],
            'Sleeves & Neck' => ['Sleeve length', 'Sleeve loose', 'Front neck', 'Back neck'],
            'Lower Body' => ['Hip', 'Seat', 'Bottom length', 'Leg loose', 'Thigh', 'Knees'],
            'Full Lengths' => ['Top length', 'Half length', 'Slit'],
        ];
    }

    /** @return array<string, string> */
    public static function labelToField(): array
    {
        return [
            'Blouse length' => 'blouse_length',
            'Shoulder' => 'shoulder',
            'Arm hole' => 'arm_hole',
            'Chest' => 'chest',
            'Waist' => 'waist',
            'Dot point' => 'dot_point',
            'Sleeve length' => 'sleeve_length',
            'Sleeve loose' => 'sleeve_loose',
            'Front neck' => 'front_neck',
            'Back neck' => 'back_neck',
            'Hip' => 'hip',
            'Seat' => 'seat',
            'Bottom length' => 'bottom_length',
            'Leg loose' => 'leg_loose',
            'Thigh' => 'thigh',
            'Knees' => 'knees',
            'Top length' => 'top_length',
            'Half length' => 'half_length',
            'Slit' => 'slit',
        ];
    }

    /** @param array<string, mixed> $input */
    public static function toApiPayload(array $input, ?string $name = null, ?string $measurementType = null): array
    {
        $payload = [
            'name' => $name ?: 'Default profile',
            'measurement_type' => $measurementType ?: 'women',
        ];

        foreach (self::labelToField() as $label => $field) {
            if (array_key_exists($field, $input) && filled($input[$field])) {
                $payload[$field] = (string) $input[$field];
            }
        }

        return $payload;
    }

    /** @return array<string, string|null> */
    public static function valuesFromProfile(?\App\Models\CustomerMeasurement $profile): array
    {
        if (! $profile) {
            return [];
        }

        $fields = $profile->apiMeasurementFields();
        $values = [];

        foreach (self::labelToField() as $label => $field) {
            $values[$field] = $fields[$field] ?? null;
        }

        return $values;
    }
}
