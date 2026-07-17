<?php

namespace App\Support;

use App\Models\CheckoutOrder;
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
        $extra = $checkout->measure_extra ?? [];
        $fields = [];

        foreach (CustomerMeasurement::EXTRA_FIELDS as $field) {
            $fields[$field] = isset($extra[$field]) ? (string) $extra[$field] : null;
        }

        return [
            'measurement_type' => $checkout->measurement_type,
            'height_cm' => $checkout->measure_height_cm,
            'chest_cm' => $checkout->measure_chest_cm,
            'waist_cm' => $checkout->measure_waist_cm,
            ...$fields,
            'extra_measurements' => $extra,
        ];
    }

    /** @return array<string, mixed> */
    public static function orderMeasurements(Order $order): array
    {
        $extra = $order->measure_extra ?? [];
        $fields = [];

        foreach (CustomerMeasurement::EXTRA_FIELDS as $field) {
            $fields[$field] = isset($extra[$field]) ? (string) $extra[$field] : null;
        }

        $fields['height'] = $order->measure_height_cm !== null
            ? (string) $order->measure_height_cm
            : (isset($extra['height']) ? (string) $extra['height'] : null);

        $fields['chest'] = $order->measure_chest_cm !== null
            ? (string) $order->measure_chest_cm
            : (isset($extra['chest']) ? (string) $extra['chest'] : null);

        $fields['waist'] = $order->measure_waist_cm !== null
            ? (string) $order->measure_waist_cm
            : (isset($extra['waist']) ? (string) $extra['waist'] : null);

        return [
            'measurement_type' => $order->measurement_type ?? self::parseMeasurementTypeFromNotes($order),
            'size' => $order->size,
            'height_cm' => $order->measure_height_cm,
            'chest_cm' => $order->measure_chest_cm,
            'waist_cm' => $order->measure_waist_cm,
            ...$fields,
            'extra_measurements' => $extra,
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
