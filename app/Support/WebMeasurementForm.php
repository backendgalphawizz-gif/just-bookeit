<?php

namespace App\Support;

class WebMeasurementForm
{
    /**
     * Full union of every measurement field, grouped by section.
     * Used wherever a stored profile is displayed (booking/checkout views).
     *
     * @return array<string, list<string>>
     */
    public static function sections(): array
    {
        return [
            'Upper Body' => ['Blouse length', 'Shoulder', 'Arm hole', 'Chest', 'Waist', 'Dot point'],
            'Sleeves & Neck' => ['Sleeve length', 'Sleeve loose', 'Front neck', 'Back neck'],
            'Lower Body' => ['Hip', 'Seat', 'Bottom length', 'Leg loose', 'Thigh', 'Knees'],
            'Full Lengths' => ['Top length', 'Half length', 'Slit'],
        ];
    }

    /**
     * Sections/fields relevant to each measurement type.
     *
     * @return array<string, array<string, list<string>>>
     */
    public static function sectionsByType(): array
    {
        return [
            'women' => [
                'Upper Body' => ['Blouse length', 'Shoulder', 'Arm hole', 'Chest', 'Waist', 'Dot point'],
                'Sleeves & Neck' => ['Sleeve length', 'Sleeve loose', 'Front neck', 'Back neck'],
                'Lower Body' => ['Hip', 'Seat', 'Bottom length', 'Leg loose', 'Thigh', 'Knees'],
                'Full Lengths' => ['Top length', 'Half length', 'Slit'],
            ],
            'men' => [
                'Upper Body' => ['Shoulder', 'Chest', 'Waist', 'Arm hole'],
                'Sleeves & Neck' => ['Sleeve length', 'Sleeve loose', 'Front neck'],
                'Lower Body' => ['Hip', 'Seat', 'Bottom length', 'Leg loose', 'Thigh', 'Knees'],
                'Full Lengths' => ['Top length'],
            ],
            'kid' => [
                'Upper Body' => ['Shoulder', 'Chest', 'Waist'],
                'Sleeves & Neck' => ['Sleeve length'],
                'Lower Body' => ['Hip', 'Bottom length', 'Thigh'],
                'Full Lengths' => ['Top length'],
            ],
        ];
    }

    public static function sectionsForType(?string $type): array
    {
        $map = self::sectionsByType();

        return $map[$type] ?? $map['women'];
    }

    /**
     * API schema for mobile apps — same type-wise fields as the website form.
     *
     * @return array{
     *     measurement_types: list<array{value: string, label: string}>,
     *     audience_to_measurement_type: array<string, string>,
     *     forms: array<string, array{sections: list<array{name: string, fields: list<array{key: string, label: string, input: string, max: int}>}>}>
     * }
     */
    public static function apiFormSchema(?string $type = null): array
    {
        $types = [
            ['value' => 'women', 'label' => 'Women'],
            ['value' => 'men', 'label' => 'Men'],
            ['value' => 'kid', 'label' => 'Kids'],
        ];

        $forms = [];
        foreach (array_keys(self::sectionsByType()) as $measurementType) {
            if ($type !== null && $type !== '' && $measurementType !== $type) {
                continue;
            }

            $forms[$measurementType] = [
                'sections' => self::apiSectionsForType($measurementType),
            ];
        }

        return [
            'measurement_types' => $types,
            'audience_to_measurement_type' => [
                'women' => 'women',
                'men' => 'men',
                'kids' => 'kid',
                'kid' => 'kid',
            ],
            'forms' => $forms,
        ];
    }

    /**
     * @return list<array{name: string, fields: list<array{key: string, label: string, input: string, max: int}>}>
     */
    public static function apiSectionsForType(?string $type): array
    {
        $labelToField = self::labelToField();
        $sections = [];

        foreach (self::sectionsForType($type) as $sectionName => $labels) {
            $fields = [];
            foreach ($labels as $label) {
                $key = $labelToField[$label] ?? null;
                if (! $key) {
                    continue;
                }

                $fields[] = [
                    'key' => $key,
                    'label' => $label,
                    'input' => 'text',
                    'max' => 50,
                ];
            }

            if ($fields !== []) {
                $sections[] = [
                    'name' => $sectionName,
                    'fields' => $fields,
                ];
            }
        }

        return $sections;
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
