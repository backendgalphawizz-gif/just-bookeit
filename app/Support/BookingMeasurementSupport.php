<?php

namespace App\Support;

use App\Models\CheckoutOrder;
use App\Models\Customer;
use App\Models\CustomerMeasurement;
use App\Models\Order;

class BookingMeasurementSupport
{
    /** @return array<string, array<int, string>> */
    public static function validationRules(): array
    {
        $rules = [
            'measurement_type' => ['nullable', 'in:women,men,kid'],
            'measure_height_cm' => ['nullable', 'integer', 'min:0', 'max:300'],
            'measure_chest_cm' => ['nullable', 'integer', 'min:0', 'max:300'],
            'measure_waist_cm' => ['nullable', 'integer', 'min:0', 'max:300'],
        ];

        $measurementRules = CustomerMeasurement::apiValidationRules(partial: true);
        unset($measurementRules['name']);

        return array_merge($rules, $measurementRules);
    }

    /**
     * Checkout / place-order: only profile reference + optional type override.
     * Full measurement values are loaded from the saved profile.
     *
     * @return array<string, array<int, string>>
     */
    public static function checkoutValidationRules(): array
    {
        return [
            'measurement_id' => ['nullable', 'integer'],
            'measurement_profile_id' => ['nullable', 'integer'],
            'measurement_type' => ['nullable', 'in:women,men,kid'],
        ];
    }

    public static function resolveProfile(Customer $customer, array $data): ?CustomerMeasurement
    {
        $id = $data['measurement_profile_id'] ?? $data['measurement_id'] ?? null;

        if ($id) {
            $profile = $customer->measurements()->whereKey((int) $id)->first();
            if ($profile) {
                return $profile;
            }
        }

        return $customer->measurements()->latest('id')->first();
    }

    /**
     * Load measurements from a saved profile; request may only override measurement_type.
     *
     * @param  array<string, mixed>  $data
     * @return array{
     *     measure_height_cm: int|null,
     *     measure_chest_cm: int|null,
     *     measure_waist_cm: int|null,
     *     measure_extra: array<string, string>|null,
     *     measurement_type: string|null
     * }
     */
    public static function normalizeFromProfileSelection(array $data, ?CustomerMeasurement $profile = null): array
    {
        if (! $profile) {
            return [
                'measure_height_cm' => null,
                'measure_chest_cm' => null,
                'measure_waist_cm' => null,
                'measure_extra' => null,
                'measurement_type' => $data['measurement_type'] ?? null,
            ];
        }

        $input = self::profileToInput($profile);

        if (! empty($data['measurement_type'])) {
            $input['measurement_type'] = $data['measurement_type'];
        }

        $normalized = CustomerMeasurement::normalizeApiPayload($input);

        return [
            'measure_height_cm' => isset($normalized['height_cm']) ? (int) $normalized['height_cm'] : null,
            'measure_chest_cm' => isset($normalized['chest_cm']) ? (int) $normalized['chest_cm'] : null,
            'measure_waist_cm' => isset($normalized['waist_cm']) ? (int) $normalized['waist_cm'] : null,
            'measure_extra' => $normalized['extra_measurements'] ?? null,
            'measurement_type' => $data['measurement_type']
                ?? $profile->measurement_type
                ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{
     *     measure_height_cm: int|null,
     *     measure_chest_cm: int|null,
     *     measure_waist_cm: int|null,
     *     measure_extra: array<string, string>|null,
     *     measurement_type: string|null
     * }
     */
    public static function normalizeForOrder(array $data, ?CustomerMeasurement $profile = null): array
    {
        $input = $profile ? self::profileToInput($profile) : [];

        foreach ($data as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $input[$key] = $value;
        }

        if (array_key_exists('measure_height_cm', $data)) {
            $input['height_cm'] = $data['measure_height_cm'];
        }

        if (array_key_exists('measure_chest_cm', $data)) {
            $input['chest_cm'] = $data['measure_chest_cm'];
        }

        if (array_key_exists('measure_waist_cm', $data)) {
            $input['waist_cm'] = $data['measure_waist_cm'];
        }

        $normalized = CustomerMeasurement::normalizeApiPayload($input);

        return [
            'measure_height_cm' => isset($normalized['height_cm']) ? (int) $normalized['height_cm'] : null,
            'measure_chest_cm' => isset($normalized['chest_cm']) ? (int) $normalized['chest_cm'] : null,
            'measure_waist_cm' => isset($normalized['waist_cm']) ? (int) $normalized['waist_cm'] : null,
            'measure_extra' => $normalized['extra_measurements'] ?? null,
            'measurement_type' => $data['measurement_type']
                ?? $profile?->measurement_type
                ?? null,
        ];
    }

    /** @return array<string, mixed> */
    public static function profileToInput(CustomerMeasurement $profile): array
    {
        $fields = $profile->apiMeasurementFields();

        $input = [
            'measurement_type' => $profile->measurement_type,
            'height_cm' => $profile->height_cm,
            'chest_cm' => $profile->chest_cm,
            'waist_cm' => $profile->waist_cm,
        ];

        foreach (CustomerMeasurement::EXTRA_FIELDS as $field) {
            if (filled($fields[$field] ?? null)) {
                $input[$field] = $fields[$field];
            }
        }

        return $input;
    }

    /** @return array<string, mixed> */
    public static function checkoutMeasurements(CheckoutOrder $checkout): array
    {
        return self::presentMeasurements(
            measurementType: $checkout->measurement_type,
            heightCm: $checkout->measure_height_cm !== null ? (int) $checkout->measure_height_cm : null,
            chestCm: $checkout->measure_chest_cm !== null ? (int) $checkout->measure_chest_cm : null,
            waistCm: $checkout->measure_waist_cm !== null ? (int) $checkout->measure_waist_cm : null,
            extra: is_array($checkout->measure_extra) ? $checkout->measure_extra : [],
            size: null,
            name: null,
        );
    }

    /** @return array<string, mixed> */
    public static function orderMeasurements(Order $order): array
    {
        $order->loadMissing(['checkoutOrder', 'customer']);

        $checkout = $order->checkoutOrder;
        $profileName = $order->customer?->measurements()->latest('id')->value('name');

        // Sub-orders historically stored measurements only on the parent checkout.
        if ($checkout && blank($order->measurement_type) && blank($order->measure_extra)
            && $order->measure_height_cm === null && $order->measure_chest_cm === null && $order->measure_waist_cm === null) {
            return self::presentMeasurements(
                measurementType: $checkout->measurement_type ?? self::parseMeasurementTypeFromNotes($order),
                heightCm: $checkout->measure_height_cm !== null ? (int) $checkout->measure_height_cm : null,
                chestCm: $checkout->measure_chest_cm !== null ? (int) $checkout->measure_chest_cm : null,
                waistCm: $checkout->measure_waist_cm !== null ? (int) $checkout->measure_waist_cm : null,
                extra: is_array($checkout->measure_extra) ? $checkout->measure_extra : [],
                size: $order->size,
                name: $profileName ? (string) $profileName : null,
            );
        }

        $extra = is_array($order->measure_extra) ? $order->measure_extra : [];

        return self::presentMeasurements(
            measurementType: $order->measurement_type ?? self::parseMeasurementTypeFromNotes($order),
            heightCm: $order->measure_height_cm !== null ? (int) $order->measure_height_cm : null,
            chestCm: $order->measure_chest_cm !== null ? (int) $order->measure_chest_cm : null,
            waistCm: $order->measure_waist_cm !== null ? (int) $order->measure_waist_cm : null,
            extra: $extra,
            size: $order->size,
            name: $profileName ? (string) $profileName : null,
        );
    }

    /**
     * Flat fields (backward compatible) + named sections for mobile UI.
     *
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public static function presentMeasurements(
        ?string $measurementType,
        ?int $heightCm,
        ?int $chestCm,
        ?int $waistCm,
        array $extra = [],
        ?string $size = null,
        ?string $name = null,
    ): array {
        $fields = [];

        foreach (CustomerMeasurement::EXTRA_FIELDS as $field) {
            $fields[$field] = isset($extra[$field]) && $extra[$field] !== ''
                ? (string) $extra[$field]
                : null;
        }

        $fields['height'] = $heightCm !== null
            ? (string) $heightCm
            : (isset($extra['height']) ? (string) $extra['height'] : null);
        $fields['chest'] = $chestCm !== null
            ? (string) $chestCm
            : (isset($extra['chest']) ? (string) $extra['chest'] : null);
        $fields['waist'] = $waistCm !== null
            ? (string) $waistCm
            : (isset($extra['waist']) ? (string) $extra['waist'] : null);

        $labelToField = WebMeasurementForm::labelToField();

        $sections = [];
        foreach (WebMeasurementForm::sectionsForType($measurementType) as $sectionName => $labels) {
            $sectionFields = [];
            foreach ($labels as $label) {
                $key = $labelToField[$label] ?? null;
                if (! $key) {
                    continue;
                }
                $sectionFields[] = [
                    'key' => $key,
                    'label' => $label,
                    'value' => $fields[$key] ?? null,
                ];
            }
            $sections[] = [
                'name' => $sectionName,
                'fields' => $sectionFields,
            ];
        }

        // Core height/chest/waist not always in the section map for every type — expose under Basics when present.
        $basics = array_values(array_filter([
            ['key' => 'height', 'label' => 'Height (cm)', 'value' => $fields['height']],
            ['key' => 'chest', 'label' => 'Chest (cm)', 'value' => $fields['chest']],
            ['key' => 'waist', 'label' => 'Waist (cm)', 'value' => $fields['waist']],
        ], fn (array $row) => filled($row['value'])));

        if ($basics !== []) {
            array_unshift($sections, [
                'name' => 'Basics',
                'fields' => $basics,
            ]);
        }

        return [
            'name' => $name,
            'measurement_type' => $measurementType,
            'size' => $size,
            'height_cm' => $heightCm,
            'chest_cm' => $chestCm,
            'waist_cm' => $waistCm,
            ...$fields,
            'extra_measurements' => $extra,
            'sections' => $sections,
            'section_names' => array_values(array_map(fn (array $section) => $section['name'], $sections)),
        ];
    }

    public static function parseMeasurementTypeFromNotes(Order $order): ?string
    {
        $notes = (string) ($order->customer_notes ?? '');

        if (preg_match('/Measurement:\s*(Women|Men|Kid)/i', $notes, $matches)) {
            return strtolower($matches[1]) === 'kid' ? 'kid' : strtolower($matches[1]);
        }

        return null;
    }
}
