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

        $data = $this->validateAddress($request);

        if ($customer->addresses()->count() === 0) {
            $data['is_default'] = true;
        }

        $address = $customer->addresses()->create([
            ...$this->prepareAddressAttributes($data, $customer),
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

        $data = $this->validateAddress($request, updating: true);

        $address->update($this->prepareAddressAttributes($data, $customer, $address));

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

    /** @return array<string, mixed> */
    protected function validateAddress(Request $request, bool $updating = false): array
    {
        $sometimes = $updating ? 'sometimes' : 'required';

        return $request->validate([
            'label' => [$updating ? 'sometimes' : 'required', 'string', 'max:50'],
            'name' => ['nullable', 'string', 'max:255'],
            'country' => [$sometimes, 'string', 'max:100'],
            'house_no' => [$sometimes, 'string', 'max:50'],
            'road_area' => [$sometimes, 'string', 'max:255'],
            'address_line' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:10'],
            'is_default' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepareAddressAttributes(array $data, Customer $customer, ?CustomerAddress $existing = null): array
    {
        $houseNo = $data['house_no'] ?? $existing?->house_no;
        $roadArea = $data['road_area'] ?? $existing?->road_area;
        $addressLine = $data['address_line'] ?? $existing?->address_line;

        if (empty($addressLine) && ($houseNo || $roadArea)) {
            $addressLine = trim(implode(', ', array_filter([$houseNo, $roadArea])));
        }

        return [
            'label' => $data['label'] ?? $existing?->label,
            'name' => $data['name'] ?? $existing?->name ?? $customer->name,
            'country' => $data['country'] ?? $existing?->country,
            'house_no' => $houseNo,
            'road_area' => $roadArea,
            'address_line' => $addressLine,
            'city' => $data['city'] ?? $existing?->city,
            'state' => $data['state'] ?? $existing?->state,
            'pincode' => $data['pincode'] ?? $existing?->pincode,
            'is_default' => $data['is_default'] ?? $existing?->is_default,
        ];
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
