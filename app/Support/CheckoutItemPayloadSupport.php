<?php

namespace App\Support;

use App\Models\CartItem;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class CheckoutItemPayloadSupport
{
    /**
     * Parse items[] from JSON string or array and index by cart/product key.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function normalizeMap(mixed $items, ?Request $request = null): array
    {
        if ($items === null || $items === '') {
            return [];
        }

        if (is_string($items)) {
            $decoded = json_decode($items, true);
            if (! is_array($decoded)) {
                throw new InvalidArgumentException('The items field must be valid JSON.');
            }
            $items = $decoded;
        }

        if (! is_array($items)) {
            throw new InvalidArgumentException('The items field must be an array.');
        }

        $map = [];

        foreach (array_values($items) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $row = self::normalizeDateFields($row);

            $row['reference_image_paths'] = self::storeReferenceImages(
                self::referenceImageFiles($request, $index),
                $row['reference_image_paths'] ?? []
            );

            $keys = self::keysForRow($row);
            foreach ($keys as $key) {
                $map[$key] = $row;
            }
        }

        return $map;
    }

    /**
     * Normalize top-level + items[] rental dates into rental_start_date / rental_end_date.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeCheckoutDates(array $data): array
    {
        $data = self::normalizeDateFields($data);

        $items = $data['items'] ?? null;
        if (is_string($items)) {
            $decoded = json_decode($items, true);
            $items = is_array($decoded) ? $decoded : null;
        }

        if ((! filled($data['rental_start_date'] ?? null) || ! filled($data['rental_end_date'] ?? null))
            && is_array($items)
        ) {
            foreach ($items as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $row = self::normalizeDateFields($row);
                if (! filled($data['rental_start_date'] ?? null) && filled($row['rental_start_date'] ?? null)) {
                    $data['rental_start_date'] = $row['rental_start_date'];
                }
                if (! filled($data['rental_end_date'] ?? null) && filled($row['rental_end_date'] ?? null)) {
                    $data['rental_end_date'] = $row['rental_end_date'];
                }
                if (filled($data['rental_start_date'] ?? null) && filled($data['rental_end_date'] ?? null)) {
                    break;
                }
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function normalizeDateFields(array $payload): array
    {
        $start = $payload['rental_start_date']
            ?? $payload['start_date']
            ?? $payload['from_date']
            ?? $payload['rental_from']
            ?? $payload['rentalStartDate']
            ?? null;

        $end = $payload['rental_end_date']
            ?? $payload['end_date']
            ?? $payload['to_date']
            ?? $payload['rental_to']
            ?? $payload['rentalEndDate']
            ?? null;

        if (filled($start)) {
            $payload['rental_start_date'] = self::normalizeDateValue($start);
        }

        if (filled($end)) {
            $payload['rental_end_date'] = self::normalizeDateValue($end);
        }

        return $payload;
    }

    protected static function normalizeDateValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return is_string($value) ? $value : null;
        }
    }

    /** @return list<string> */
    public static function keysForRow(array $row): array
    {
        $keys = [];

        if (! empty($row['cart_item_id'])) {
            $keys[] = 'cart:'.(int) $row['cart_item_id'];
        }

        if (! empty($row['portfolio_item_id'])) {
            $variantId = (int) ($row['portfolio_item_variant_id'] ?? 0);
            $keys[] = 'product:'.(int) $row['portfolio_item_id'].':'.$variantId;
        }

        return $keys;
    }

    public static function resolveOverride(array $map, CartItem $cartItem): array
    {
        $variantId = (int) ($cartItem->portfolio_item_variant_id ?? 0);

        return $map['cart:'.$cartItem->id]
            ?? $map['product:'.$cartItem->portfolio_item_id.':'.$variantId]
            ?? $map['product:'.$cartItem->portfolio_item_id.':0']
            ?? [];
    }

    /**
     * @param  array<string, mixed>  $override
     * @param  array<string, mixed>  $checkoutData
     * @return array{start: ?string, end: ?string, days: ?int, billing_days: int}
     */
    public static function rentalWindow(array $override, array $checkoutData, bool $requiresRentalPeriod = true): array
    {
        // Fashion designer: only keep dates when the line item itself sends them.
        $start = $override['rental_start_date'] ?? null;
        $end = $override['rental_end_date'] ?? null;

        if ($requiresRentalPeriod) {
            $start = $start ?? ($checkoutData['rental_start_date'] ?? null);
            $end = $end ?? ($checkoutData['rental_end_date'] ?? null);
        }

        return [
            'start' => $start,
            'end' => $end,
            'days' => \App\Services\Booking\BookingPricingService::rentalDays($start, $end),
            'billing_days' => \App\Services\Booking\BookingPricingService::billingDays($start, $end),
        ];
    }

    public static function resolveMeasurementProfileId(Customer $customer, array $data, array $override = []): ?int
    {
        $id = $override['measurement_id']
            ?? $override['measurement_profile_id']
            ?? $data['measurement_id']
            ?? $data['measurement_profile_id']
            ?? null;

        if ($id === null || $id === '') {
            return null;
        }

        $profile = $customer->measurements()->whereKey((int) $id)->first();

        return $profile?->id;
    }

    /**
     * @param  array<string, mixed>  $override
     * @param  array<string, mixed>  $checkoutData
     * @return array<string, mixed>
     */
    public static function itemSnapshotExtras(array $override, array $checkoutData, bool $requiresRentalPeriod = true): array
    {
        $rental = self::rentalWindow($override, $checkoutData, $requiresRentalPeriod);

        return array_filter([
            'rental_start_date' => $rental['start'],
            'rental_end_date' => $rental['end'],
            'event_date' => $override['event_date'] ?? $checkoutData['event_date'] ?? null,
            'customer_notes' => filled($override['customer_notes'] ?? null) ? trim((string) $override['customer_notes']) : null,
            'measurement_profile_id' => $override['measurement_id'] ?? $override['measurement_profile_id'] ?? null,
            'service_type' => $override['service_type'] ?? null,
            'reference_image_paths' => $override['reference_image_paths'] ?? null,
            'size' => $override['size'] ?? null,
            'color' => $override['color'] ?? null,
            'cart_item_id' => $override['cart_item_id'] ?? null,
        ], fn ($value) => $value !== null && $value !== '' && $value !== []);
    }

    /** @return list<UploadedFile> */
    protected static function referenceImageFiles(?Request $request, int $index): array
    {
        if (! $request) {
            return [];
        }

        $candidates = [
            "items.{$index}.reference_images",
            "items.{$index}.reference_images.*",
            "items[{$index}][reference_images]",
            "items[{$index}][reference_images][]",
        ];

        foreach ($candidates as $key) {
            $files = $request->file($key);
            if ($files instanceof UploadedFile) {
                return [$files];
            }
            if (is_array($files)) {
                return array_values(array_filter($files, fn ($file) => $file instanceof UploadedFile));
            }
        }

        return [];
    }

    /**
     * @param  list<UploadedFile>  $files
     * @param  list<string>|mixed  $existing
     * @return list<string>
     */
    protected static function storeReferenceImages(array $files, mixed $existing = []): array
    {
        $paths = is_array($existing) ? $existing : [];

        foreach ($files as $file) {
            $paths[] = StoresUploadedFiles::store($file, 'orders/reference-images');
        }

        return array_values($paths);
    }
}
