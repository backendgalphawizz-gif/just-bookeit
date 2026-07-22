<?php

namespace App\Support;

use App\Models\City;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WebLocation
{
    public const SESSION_KEY = 'web_location';

    /** @return array<string, mixed>|null */
    public static function get(Request $request): ?array
    {
        $stored = $request->session()->get(self::SESSION_KEY);

        return is_array($stored) ? $stored : null;
    }

    public static function label(Request $request): string
    {
        $stored = self::get($request);
        if (filled($stored['label'] ?? null)) {
            return (string) $stored['label'];
        }

        $customer = Auth::guard('customer')->user();
        if ($customer instanceof Customer) {
            $address = $customer->defaultAddress();
            if ($address) {
                return self::addressLabel($address);
            }

            if (filled($customer->city)) {
                return $customer->city;
            }
        }

        return 'Choose location';
    }

    /**
     * Prefer a saved customer location when the session has none yet.
     */
    public static function bootstrapFromCustomer(Request $request): void
    {
        if (self::get($request) !== null) {
            return;
        }

        $customer = Auth::guard('customer')->user();
        if (! $customer instanceof Customer) {
            return;
        }

        $address = $customer->defaultAddress();
        if ($address) {
            self::put($request, self::fromAddress($address));

            return;
        }

        if (! filled($customer->city)) {
            return;
        }

        $city = City::query()
            ->where('is_active', true)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) $customer->city))])
            ->with(['state.country'])
            ->first();

        if ($city) {
            self::put($request, self::fromCity($city));
        }
    }

    public static function needsAutoDetect(Request $request): bool
    {
        return self::get($request) === null;
    }

    /**
     * Approximate city from the visitor IP when GPS is unavailable.
     */
    public static function bootstrapFromIp(Request $request): void
    {
        if (self::get($request) !== null) {
            return;
        }

        $payload = self::payloadFromIp($request);
        if (! $payload) {
            return;
        }

        self::put($request, $payload);

        $customer = Auth::guard('customer')->user();
        if ($customer instanceof Customer && ! $customer->is_guest && ! filled($customer->city) && filled($payload['city'] ?? null)) {
            $customer->forceFill(['city' => $payload['city']])->save();
        }
    }

    /** @param  array<string, mixed>  $payload */
    public static function put(Request $request, array $payload): void
    {
        $request->session()->put(self::SESSION_KEY, $payload);
    }

    /** @return array<string, mixed>|null */
    public static function payloadFromIp(Request $request): ?array
    {
        $lookupIp = self::publicClientIp($request);
        $cacheKey = 'web_location_ip_payload_v1:'.sha1($lookupIp ?: 'egress');

        $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
        if (is_array($cached)) {
            if (filled($cached['label'] ?? null)) {
                return $cached;
            }

            // Recent miss — avoid hammering the provider.
            return null;
        }

        $geo = self::fetchIpGeo($lookupIp);
        if ($geo === [] || (! filled($geo['city'] ?? null) && ! filled($geo['region'] ?? null))) {
            \Illuminate\Support\Facades\Cache::put($cacheKey, [], 60);

            return null;
        }

        $candidates = array_values(array_filter([
            $geo['city'] ?? null,
            $geo['region'] ?? null,
        ]));

        $city = self::matchCityByNames($candidates);
        $payload = $city
            ? self::fromCity($city)
            : self::fromGeoNames(
                (string) ($geo['city'] ?: ($geo['region'] ?? 'My location')),
                (string) ($geo['region'] ?? ''),
                'India'
            );

        if (isset($geo['latitude'], $geo['longitude'])) {
            $payload['latitude'] = (float) $geo['latitude'];
            $payload['longitude'] = (float) $geo['longitude'];
        } else {
            $payload = self::ensureCoordinates($payload);
        }

        \Illuminate\Support\Facades\Cache::put($cacheKey, $payload, 900);

        return $payload;
    }

    /** @return array<string, mixed> */
    public static function fromGeoNames(string $city, string $state = '', string $country = 'India'): array
    {
        $label = $city;
        if ($state !== '' && ! str_contains(mb_strtolower($label), mb_strtolower($state))) {
            $label .= ', '.$state;
        }

        return [
            'type' => 'geo',
            'city_id' => null,
            'city' => $city,
            'state' => $state !== '' ? $state : null,
            'country' => $country,
            'label' => $label,
        ];
    }

    public static function resolveCityFromIp(Request $request): ?City
    {
        $payload = self::payloadFromIp($request);
        if (! $payload || empty($payload['city_id'])) {
            return null;
        }

        return City::query()
            ->where('is_active', true)
            ->with(['state.country'])
            ->find($payload['city_id']);
    }

    public static function resolveCityFromCoordinates(float $latitude, float $longitude): ?City
    {
        return self::matchCityByNames(self::placeNameCandidates($latitude, $longitude));
    }

    /** @param  list<string>  $candidates */
    public static function matchCityByNames(array $candidates): ?City
    {
        if ($candidates === []) {
            return null;
        }

        $aliases = [
            'bengaluru' => 'bangalore',
            'bangalore' => 'bengaluru',
            'bombay' => 'mumbai',
            'calcutta' => 'kolkata',
            'madras' => 'chennai',
            'gurugram' => 'gurgaon',
            'gurgaon' => 'gurugram',
        ];

        $normalized = collect($candidates)
            ->map(fn (string $name) => mb_strtolower(trim($name)))
            ->filter()
            ->unique()
            ->values();

        foreach ($normalized as $name) {
            $city = City::query()
                ->where('is_active', true)
                ->whereRaw('LOWER(name) = ?', [$name])
                ->with(['state.country'])
                ->first();

            if ($city) {
                return $city;
            }

            $alias = $aliases[$name] ?? null;
            if ($alias) {
                $city = City::query()
                    ->where('is_active', true)
                    ->whereRaw('LOWER(name) = ?', [$alias])
                    ->with(['state.country'])
                    ->first();

                if ($city) {
                    return $city;
                }
            }
        }

        foreach ($normalized as $name) {
            if (mb_strlen($name) < 3) {
                continue;
            }

            $city = City::query()
                ->where('is_active', true)
                ->whereRaw('LOWER(name) LIKE ?', ['%'.$name.'%'])
                ->with(['state.country'])
                ->orderByRaw('CHAR_LENGTH(name) asc')
                ->first();

            if ($city) {
                return $city;
            }
        }

        return null;
    }

    protected static function publicClientIp(Request $request): ?string
    {
        $candidates = [
            $request->ip(),
            $request->header('CF-Connecting-IP'),
            $request->header('X-Real-IP'),
        ];

        $forwarded = $request->header('X-Forwarded-For');
        if (is_string($forwarded) && $forwarded !== '') {
            $candidates[] = trim(explode(',', $forwarded)[0]);
        }

        foreach ($candidates as $ip) {
            if (is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }

        return null;
    }

    /** @return array{city?: string, region?: string} */
    protected static function fetchIpGeo(?string $ip): array
    {
        $fromIpApi = self::fetchIpApiGeo($ip);
        if ($fromIpApi !== []) {
            return $fromIpApi;
        }

        return self::fetchIpWhoGeo($ip);
    }

    /** @return array{city?: string, region?: string} */
    protected static function fetchIpApiGeo(?string $ip): array
    {
        $query = [
            'fields' => 'status,message,city,regionName,country,lat,lon,query',
        ];

        $url = $ip
            ? 'http://ip-api.com/json/'.rawurlencode($ip).'?'.http_build_query($query)
            : 'http://ip-api.com/json/?'.http_build_query($query);

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(6)->get($url);
        } catch (\Throwable) {
            return [];
        }

        if (! $response->ok() || ($response->json('status') ?? '') !== 'success') {
            return [];
        }

        return array_filter([
            'city' => (string) ($response->json('city') ?? ''),
            'region' => (string) ($response->json('regionName') ?? ''),
            'latitude' => is_numeric($response->json('lat')) ? (float) $response->json('lat') : null,
            'longitude' => is_numeric($response->json('lon')) ? (float) $response->json('lon') : null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    /** @return array{city?: string, region?: string, latitude?: float, longitude?: float} */
    protected static function fetchIpWhoGeo(?string $ip): array
    {
        $url = $ip
            ? 'https://ipwho.is/'.rawurlencode($ip)
            : 'https://ipwho.is/';

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(6)
                ->acceptJson()
                ->get($url);
        } catch (\Throwable) {
            return [];
        }

        if (! $response->ok() || ! ($response->json('success') ?? false)) {
            return [];
        }

        return array_filter([
            'city' => (string) ($response->json('city') ?? ''),
            'region' => (string) ($response->json('region') ?? ''),
            'latitude' => is_numeric($response->json('latitude')) ? (float) $response->json('latitude') : null,
            'longitude' => is_numeric($response->json('longitude')) ? (float) $response->json('longitude') : null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    /** @return list<string> */
    public static function placeNameCandidates(float $latitude, float $longitude): array
    {
        $key = config('services.google.maps_api_key');
        if (filled($key)) {
            $names = self::googlePlaceNames($latitude, $longitude, (string) $key);
            if ($names !== []) {
                return $names;
            }
        }

        return self::nominatimPlaceNames($latitude, $longitude);
    }

    /** @return list<string> */
    protected static function googlePlaceNames(float $latitude, float $longitude, string $key): array
    {
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?'.http_build_query([
            'latlng' => $latitude.','.$longitude,
            'key' => $key,
        ]);

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(8)->get($url);
        } catch (\Throwable) {
            return [];
        }

        if (! $response->ok()) {
            return [];
        }

        $names = [];
        foreach ($response->json('results') ?? [] as $result) {
            foreach ($result['address_components'] ?? [] as $component) {
                $types = $component['types'] ?? [];
                if (array_intersect($types, ['locality', 'administrative_area_level_2', 'administrative_area_level_3', 'sublocality', 'postal_town'])) {
                    $names[] = (string) ($component['long_name'] ?? '');
                }
            }
        }

        return array_values(array_filter(array_unique($names)));
    }

    /** @return list<string> */
    protected static function nominatimPlaceNames(float $latitude, float $longitude): array
    {
        $url = 'https://nominatim.openstreetmap.org/reverse?'.http_build_query([
            'lat' => $latitude,
            'lon' => $longitude,
            'format' => 'jsonv2',
            'addressdetails' => 1,
        ]);

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(8)
                ->withHeaders([
                    'User-Agent' => 'JustBookIT/1.0 (web location detect)',
                    'Accept' => 'application/json',
                ])
                ->get($url);
        } catch (\Throwable) {
            return [];
        }

        if (! $response->ok()) {
            return [];
        }

        $address = $response->json('address') ?? [];
        $names = [
            $address['city'] ?? null,
            $address['town'] ?? null,
            $address['village'] ?? null,
            $address['municipality'] ?? null,
            $address['county'] ?? null,
            $address['state_district'] ?? null,
        ];

        return array_values(array_filter(array_unique(array_map(
            fn ($name) => is_string($name) ? trim($name) : '',
            $names
        ))));
    }

    public static function fromCity(City $city): array
    {
        $city->loadMissing('state.country');
        $state = $city->state;
        $country = $state?->country;

        $label = $city->name;
        if ($state?->name) {
            $label .= ', '.$state->name;
        }

        return self::ensureCoordinates([
            'type' => 'city',
            'city_id' => $city->id,
            'city' => $city->name,
            'state' => $state?->name,
            'country' => $country?->name ?? 'India',
            'label' => $label,
        ]);
    }

    public static function fromAddress(CustomerAddress $address): array
    {
        return self::ensureCoordinates([
            'type' => 'address',
            'address_id' => $address->id,
            'city' => $address->city,
            'state' => $address->state,
            'country' => $address->country ?? 'India',
            'label' => self::addressLabel($address),
        ]);
    }

    /**
     * @return array{latitude: float, longitude: float}|null
     */
    public static function coordinates(?array $stored): ?array
    {
        if (! is_array($stored)) {
            return null;
        }

        $latitude = $stored['latitude'] ?? null;
        $longitude = $stored['longitude'] ?? null;

        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            $stored = self::ensureCoordinates($stored);
            $latitude = $stored['latitude'] ?? null;
            $longitude = $stored['longitude'] ?? null;
        }

        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            return null;
        }

        return [
            'latitude' => (float) $latitude,
            'longitude' => (float) $longitude,
        ];
    }

    /**
     * Fill latitude/longitude on a location payload when missing (geocode city/address).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function ensureCoordinates(array $payload): array
    {
        if (is_numeric($payload['latitude'] ?? null) && is_numeric($payload['longitude'] ?? null)) {
            return $payload;
        }

        $query = trim(implode(', ', array_filter([
            $payload['label'] ?? null,
            $payload['city'] ?? null,
            $payload['state'] ?? null,
            $payload['country'] ?? 'India',
        ])));

        if ($query === '') {
            return $payload;
        }

        $coords = self::geocodeQuery($query);
        if ($coords === null && filled($payload['city'] ?? null)) {
            $coords = self::geocodeQuery(trim(($payload['city'] ?? '').', India'));
        }

        if ($coords === null) {
            return $payload;
        }

        $payload['latitude'] = $coords['latitude'];
        $payload['longitude'] = $coords['longitude'];

        return $payload;
    }

    /**
     * @return array{latitude: float, longitude: float}|null
     */
    public static function geocodeQuery(string $query): ?array
    {
        $query = trim($query);
        if ($query === '') {
            return null;
        }

        $cacheKey = 'web_location_geocode_v1:'.sha1(mb_strtolower($query));
        $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
        if (is_array($cached) && isset($cached['latitude'], $cached['longitude'])) {
            return [
                'latitude' => (float) $cached['latitude'],
                'longitude' => (float) $cached['longitude'],
            ];
        }

        $coords = self::geocodeWithGoogle($query) ?? self::geocodeWithNominatim($query);
        if ($coords === null) {
            \Illuminate\Support\Facades\Cache::put($cacheKey, [], 300);

            return null;
        }

        \Illuminate\Support\Facades\Cache::put($cacheKey, $coords, 86400);

        return $coords;
    }

    /**
     * @return array{latitude: float, longitude: float}|null
     */
    protected static function geocodeWithGoogle(string $query): ?array
    {
        $key = config('services.google.maps_api_key');
        if (! filled($key)) {
            return null;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(8)->get(
                'https://maps.googleapis.com/maps/api/geocode/json',
                ['address' => $query, 'key' => $key]
            );
        } catch (\Throwable) {
            return null;
        }

        if (! $response->ok()) {
            return null;
        }

        $location = $response->json('results.0.geometry.location');
        if (! is_array($location) || ! isset($location['lat'], $location['lng'])) {
            return null;
        }

        return [
            'latitude' => (float) $location['lat'],
            'longitude' => (float) $location['lng'],
        ];
    }

    /**
     * @return array{latitude: float, longitude: float}|null
     */
    protected static function geocodeWithNominatim(string $query): ?array
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(8)
                ->withHeaders([
                    'User-Agent' => 'JustBookIT/1.0 (web location geocode)',
                    'Accept' => 'application/json',
                ])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'jsonv2',
                    'limit' => 1,
                ]);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->ok()) {
            return null;
        }

        $row = $response->json('0');
        if (! is_array($row) || ! isset($row['lat'], $row['lon'])) {
            return null;
        }

        return [
            'latitude' => (float) $row['lat'],
            'longitude' => (float) $row['lon'],
        ];
    }

    public static function addressLabel(CustomerAddress $address): string
    {
        $street = trim($address->address_line ?: implode(', ', array_filter([
            $address->house_no,
            $address->road_area,
        ])));

        if ($street !== '') {
            return Str::limit($street.', '.$address->city, 42);
        }

        return trim(implode(', ', array_filter([$address->city, $address->state])), ', ') ?: 'Saved address';
    }

    /** @return list<array{id: int, name: string, state: string, country: string, search: string}> */
    public static function cityOptions(): array
    {
        return City::query()
            ->where('is_active', true)
            ->with(['state.country'])
            ->whereHas('state', fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get()
            ->map(function (City $city) {
                $state = $city->state?->name ?? '';
                $country = $city->state?->country?->name ?? 'India';

                return [
                    'id' => $city->id,
                    'name' => $city->name,
                    'state' => $state,
                    'country' => $country,
                    'search' => mb_strtolower(trim($city->name.' '.$state.' '.$country)),
                ];
            })
            ->values()
            ->all();
    }
}
