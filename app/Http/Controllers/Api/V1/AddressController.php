<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Support\Api\CustomerApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        $addresses = $customer->addresses()->orderByDesc('is_default')->orderByDesc('id')->get();

        return $this->success([
            'items' => $addresses->map(fn (CustomerAddress $address) => CustomerApiPresenter::savedAddress($address))->values()->all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        $data = $request->validate([
            'label' => ['required', 'string', 'max:50'],
            'name' => ['nullable', 'string', 'max:255'],
            'address_line' => ['required', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:10'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if ($customer->addresses()->count() === 0) {
            $data['is_default'] = true;
        }

        $address = $customer->addresses()->create([
            ...$data,
            'name' => $data['name'] ?? $customer->name,
        ]);

        if ($request->boolean('is_default')) {
            $this->markDefault($customer->id, $address->id);
            $address->refresh();
        }

        return $this->success([
            'address' => CustomerApiPresenter::savedAddress($address),
        ], 'Address saved.', 201);
    }

    public function update(Request $request, CustomerAddress $address): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        abort_unless($address->customer_id === $customer->id, 403);

        $data = $request->validate([
            'label' => ['sometimes', 'string', 'max:50'],
            'name' => ['nullable', 'string', 'max:255'],
            'address_line' => ['sometimes', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:10'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $address->update($data);

        if ($request->boolean('is_default')) {
            $this->markDefault($customer->id, $address->id);
            $address->refresh();
        }

        return $this->success([
            'address' => CustomerApiPresenter::savedAddress($address),
        ], 'Address updated.');
    }

    public function destroy(Request $request, CustomerAddress $address): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        abort_unless($address->customer_id === $customer->id, 403);

        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $next = $customer->addresses()->latest('id')->first();
            $next?->update(['is_default' => true]);
        }

        return $this->success(null, 'Address deleted.');
    }

    protected function markDefault(int $customerId, int $addressId): void
    {
        CustomerAddress::query()
            ->where('customer_id', $customerId)
            ->where('id', '!=', $addressId)
            ->update(['is_default' => false]);

        CustomerAddress::query()->whereKey($addressId)->update(['is_default' => true]);
    }
}
