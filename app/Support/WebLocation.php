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

        return 'Mumbai, India';
    }

    /** @param  array<string, mixed>  $payload */
    public static function put(Request $request, array $payload): void
    {
        $request->session()->put(self::SESSION_KEY, $payload);
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

        return [
            'type' => 'city',
            'city_id' => $city->id,
            'city' => $city->name,
            'state' => $state?->name,
            'country' => $country?->name ?? 'India',
            'label' => $label,
        ];
    }

    public static function fromAddress(CustomerAddress $address): array
    {
        return [
            'type' => 'address',
            'address_id' => $address->id,
            'city' => $address->city,
            'state' => $address->state,
            'country' => $address->country ?? 'India',
            'label' => self::addressLabel($address),
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
