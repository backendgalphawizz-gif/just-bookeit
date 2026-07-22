<?php

namespace App\Support\Api;

use App\Models\PlatformSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class VendorProximityFilter
{
    public const DEFAULT_RADIUS_KM = 25.0;

    public const SETTING_KEY = 'discovery_radius_km';

    /** @return array<string, array<int, string>> */
    public static function validationRules(): array
    {
        return [
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:lng'],
            'lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:lat'],
        ];
    }

    public static function radiusKm(): float
    {
        $value = PlatformSetting::get(self::SETTING_KEY, self::DEFAULT_RADIUS_KM);

        $radius = is_numeric($value) ? (float) $value : self::DEFAULT_RADIUS_KM;

        return max(0.1, min(500.0, $radius));
    }

    /**
     * @return array{latitude: float, longitude: float}|null
     */
    public static function coordinatesFromRequest(Request $request): ?array
    {
        $latitude = $request->input('latitude', $request->input('lat'));
        $longitude = $request->input('longitude', $request->input('lng'));

        if ($latitude === null || $latitude === '' || $longitude === null || $longitude === '') {
            return null;
        }

        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            return null;
        }

        return [
            'latitude' => (float) $latitude,
            'longitude' => (float) $longitude,
        ];
    }

    /** Filter portfolio/catalog queries via vendor coordinates. */
    public static function applyToCatalogQuery(Builder $query, Request $request): Builder
    {
        $coords = self::coordinatesFromRequest($request);

        if ($coords === null) {
            return $query;
        }

        return $query->whereHas(
            'vendor',
            fn (Builder $vendor) => self::applyOnVendorQuery($vendor, $coords['latitude'], $coords['longitude'])
        );
    }

    public static function applyOnVendorQuery(Builder $query, float $latitude, float $longitude, ?float $radiusKm = null): Builder
    {
        $radiusKm ??= self::radiusKm();

        // Haversine distance in km (Earth radius ≈ 6371).
        $distanceSql = '(6371 * acos(least(1, greatest(-1,
            cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?))
            + sin(radians(?)) * sin(radians(latitude))
        ))))';

        // Keep vendors without coordinates visible; only radius-filter those that have GPS.
        return $query->where(function (Builder $outer) use ($distanceSql, $latitude, $longitude, $radiusKm) {
            $outer->where(function (Builder $missing) {
                $missing->whereNull('latitude')->orWhereNull('longitude');
            })->orWhere(function (Builder $located) use ($distanceSql, $latitude, $longitude, $radiusKm) {
                $located->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->whereRaw("{$distanceSql} <= ?", [
                        $latitude,
                        $longitude,
                        $latitude,
                        $radiusKm,
                    ]);
            });
        });
    }

    public static function applyFromRequest(Builder $vendorQuery, Request $request): Builder
    {
        $coords = self::coordinatesFromRequest($request);

        if ($coords === null) {
            return $vendorQuery;
        }

        return self::applyOnVendorQuery($vendorQuery, $coords['latitude'], $coords['longitude']);
    }

    /** @return array<string, mixed> */
    public static function appliedMeta(Request $request): array
    {
        $coords = self::coordinatesFromRequest($request);

        if ($coords === null) {
            return [];
        }

        return [
            'latitude' => $coords['latitude'],
            'longitude' => $coords['longitude'],
            'radius_km' => self::radiusKm(),
        ];
    }
}
