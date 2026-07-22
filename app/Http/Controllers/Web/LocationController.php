<?php

namespace App\Http\Controllers\Web;

use App\Models\City;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Support\WebLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LocationController extends WebController
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'city_id' => ['nullable', 'integer', 'exists:cities,id', 'required_without:address_id'],
            'address_id' => ['nullable', 'integer', 'exists:customer_addresses,id', 'required_without:city_id'],
        ]);

        if ($request->filled('address_id')) {
            $customer = Auth::guard('customer')->user();
            abort_unless($customer instanceof Customer && ! $customer->is_guest, 403);

            $address = CustomerAddress::query()
                ->where('customer_id', $customer->id)
                ->findOrFail($data['address_id']);

            WebLocation::put($request, WebLocation::fromAddress($address));

            if (filled($address->city)) {
                $customer->update(['city' => $address->city]);
            }

            return back()->with('success', 'Location updated.');
        }

        $city = City::query()
            ->where('is_active', true)
            ->with(['state.country'])
            ->findOrFail($data['city_id']);

        WebLocation::put($request, WebLocation::fromCity($city));

        $customer = Auth::guard('customer')->user();
        if ($customer instanceof Customer && ! $customer->is_guest) {
            $customer->update(['city' => $city->name]);
        }

        return back()->with('success', 'Location updated.');
    }

    public function detect(Request $request): JsonResponse
    {
        $data = $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
        ]);

        $payload = null;
        $source = 'ip';

        if (isset($data['latitude'], $data['longitude'])) {
            $city = WebLocation::resolveCityFromCoordinates(
                (float) $data['latitude'],
                (float) $data['longitude']
            );

            if ($city) {
                $payload = WebLocation::fromCity($city);
                $source = 'gps';
            } else {
                $payload = WebLocation::fromGeoNames('My location');
                $source = 'gps';
            }

            $payload['latitude'] = (float) $data['latitude'];
            $payload['longitude'] = (float) $data['longitude'];
        }

        $payload ??= WebLocation::payloadFromIp($request);

        if (! $payload) {
            return response()->json([
                'ok' => false,
                'message' => 'Could not detect your city. Please choose one from the list.',
            ], 422);
        }

        $payload = WebLocation::ensureCoordinates($payload);
        WebLocation::put($request, $payload);

        $customer = Auth::guard('customer')->user();
        if ($customer instanceof Customer && ! $customer->is_guest && filled($payload['city'] ?? null)) {
            $customer->update(['city' => $payload['city']]);
        }

        return response()->json([
            'ok' => true,
            'label' => $payload['label'],
            'city_id' => $payload['city_id'] ?? null,
            'city' => $payload['city'] ?? null,
            'source' => $source,
        ]);
    }
}
