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
     * Multipart uploads often send files as items[0][reference_images][], which makes PHP
     * overwrite a sibling form field named "items" (JSON string). Prefer items_json in that case.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function hydrateItemsPayload(array $data, ?Request $request = null): array
    {
        if ($request) {
            foreach (['rental_start_date', 'rental_end_date', 'start_date', 'end_date', 'from_date', 'to_date', 'items_json', 'cart_items', 'line_items'] as $key) {
                if (blank($data[$key] ?? null) && $request->filled($key)) {
                    $data[$key] = $request->input($key);
                }
            }

            // Nested multipart items[0][rental_start_date] survives alongside file uploads.
            if (blank($data['items'] ?? null) || (is_array($data['items'] ?? null) && ! self::parseItemsCandidate($data['items']))) {
                $requestItems = $request->input('items');
                if (is_array($requestItems) && self::parseItemsCandidate($requestItems)) {
                    $data['items'] = $requestItems;
                }
            }
        }

        // Laravel validate()/all() merges $_FILES into "items", which hides the JSON string that
        // Flutter still sent as a text field named "items" alongside items[N][reference_images][].
        // Prefer the raw POST/input string (or items_json) over that file-merged shell.
        $itemsCandidate = self::preferredItemsCandidate($data, $request);

        $resolved = self::resolveItemsList(
            $data['items_json'] ?? ($request?->input('items_json')),
            $data['cart_items'] ?? ($request?->input('cart_items')),
            $data['line_items'] ?? ($request?->input('line_items')),
            $itemsCandidate,
        );

        if ($resolved !== null) {
            $data['items'] = $resolved;
        } elseif (self::itemsJsonWasClobberedByFiles($data, $request)) {
            throw new InvalidArgumentException(
                'Cart line JSON was overwritten by file fields named items[N][reference_images][]. '
                .'Send the lines as items_json (keep image fields as items[N][reference_images][]), '
                .'or keep field name items and upload images as reference_images[N][] instead. '
                .'Rental dress/jewellery lines need rental_start_date and rental_end_date; fashion designer lines do not.'
            );
        }

        unset($data['items_json'], $data['cart_items'], $data['line_items']);

        return $data;
    }

    /**
     * Recover the cart-line JSON when validate()/all() merged upload files over the "items" text field.
     *
     * @param  array<string, mixed>  $data
     */
    protected static function preferredItemsCandidate(array $data, ?Request $request): mixed
    {
        $fromData = $data['items'] ?? null;
        $fromInput = $request?->input('items');
        $fromPost = $request?->request->all()['items'] ?? null;

        foreach ([$fromInput, $fromPost, $fromData] as $candidate) {
            if (is_string($candidate) && self::parseItemsCandidate($candidate) !== null) {
                return $candidate;
            }
        }

        foreach ([$fromInput, $fromPost, $fromData] as $candidate) {
            if (is_array($candidate) && self::parseItemsCandidate($candidate) !== null) {
                return $candidate;
            }
        }

        return $fromData ?? $fromInput ?? $fromPost;
    }

    /**
     * True when multipart left only a file shell under "items" and no alternate JSON field.
     *
     * @param  array<string, mixed>  $data
     */
    public static function itemsJsonWasClobberedByFiles(array $data, ?Request $request = null): bool
    {
        $items = $data['items'] ?? $request?->input('items');

        if (! is_array($items) || self::parseItemsCandidate($items) !== null) {
            return false;
        }

        foreach (['items_json', 'cart_items', 'line_items'] as $key) {
            if (filled($data[$key] ?? null) || $request?->filled($key)) {
                return false;
            }
        }

        // File-only shell, or empty array after PHP merged items[N][reference_images][] over the JSON string.
        return $items === [] || self::arrayLooksLikeFileShell($items);
    }

    /** @param  array<mixed>  $items */
    protected static function arrayLooksLikeFileShell(array $items): bool
    {
        foreach ($items as $row) {
            if (! is_array($row)) {
                return false;
            }
            if (self::rowHasItemIdentity($row)) {
                return false;
            }
            if (! array_key_exists('reference_images', $row) && ! array_key_exists('reference_image', $row)) {
                // Still treat unknown nested arrays without identity as clobbered shells.
                continue;
            }
        }

        return $items !== [];
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    public static function resolveItemsList(mixed ...$candidates): ?array
    {
        foreach ($candidates as $candidate) {
            $parsed = self::parseItemsCandidate($candidate);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    protected static function parseItemsCandidate(mixed $candidate): ?array
    {
        if ($candidate === null || $candidate === '') {
            return null;
        }

        if (is_string($candidate)) {
            $decoded = json_decode($candidate, true);
            // Some clients double-encode the JSON string.
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }
            if (! is_array($decoded)) {
                return null;
            }
            $candidate = $decoded;
        }

        if (! is_array($candidate)) {
            return null;
        }

        $rows = [];
        foreach (array_values($candidate) as $row) {
            if (! is_array($row)) {
                continue;
            }

            // Ignore PHP's file-only shell left after items JSON was clobbered by items[N][... ] files.
            if (! self::rowHasItemIdentity($row)) {
                continue;
            }

            $rows[] = $row;
        }

        return $rows === [] ? null : $rows;
    }

    protected static function rowHasItemIdentity(array $row): bool
    {
        return filled($row['cart_item_id'] ?? null)
            || filled($row['portfolio_item_id'] ?? null)
            || filled($row['rental_start_date'] ?? null)
            || filled($row['rental_end_date'] ?? null)
            || filled($row['start_date'] ?? null)
            || filled($row['end_date'] ?? null)
            || filled($row['service_type'] ?? null)
            || filled($row['customer_notes'] ?? null)
            || filled($row['size'] ?? null);
    }

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

            if (! self::rowHasItemIdentity($row) && self::referenceImageFiles($request, $index) === []) {
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

        if (is_array($value)) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return \Carbon\Carbon::instance($value)->toDateString();
        }

        try {
            return \Carbon\Carbon::parse(trim((string) $value))->toDateString();
        } catch (\Throwable) {
            return is_string($value) ? trim($value) : null;
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

        // Prefer non-colliding keys first so clients can keep a text field named "items".
        $candidates = [
            "reference_images.{$index}",
            "reference_images.{$index}.*",
            "reference_images[{$index}]",
            "reference_images[{$index}][]",
            "item_images.{$index}",
            "item_images.{$index}.*",
            "item_images[{$index}]",
            "item_images[{$index}][]",
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
