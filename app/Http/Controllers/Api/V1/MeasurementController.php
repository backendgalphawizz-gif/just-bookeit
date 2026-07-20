<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Customer;
use App\Models\CustomerMeasurement;
use App\Support\Api\CustomerApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeasurementController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        $profiles = $customer->measurements()->latest('updated_at')->get();

        return $this->success([
            'items' => $profiles->map(fn (CustomerMeasurement $profile) => CustomerApiPresenter::measurementDetail($profile))->values()->all(),
        ]);
    }

    public function show(Request $request, CustomerMeasurement $measurement): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        abort_unless($measurement->customer_id === $customer->id, 403);

        return $this->success(CustomerApiPresenter::measurementDetail($measurement));
    }

    public function store(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        $profile = $customer->measurements()->create(
            $this->validatedPayload($request)
        );

        return $this->success([
            'measurement' => CustomerApiPresenter::measurementDetail($profile),
        ], 'Measurements saved.', 201);
    }

    public function update(Request $request, CustomerMeasurement $measurement): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        abort_unless($measurement->customer_id === $customer->id, 403);

        $measurement->update(
            $this->validatedPayload($request, $measurement)
        );

        return $this->success([
            'measurement' => CustomerApiPresenter::measurementDetail($measurement->fresh()),
        ], 'Measurements updated.');
    }

    public function destroy(Request $request, CustomerMeasurement $measurement): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        abort_unless($measurement->customer_id === $customer->id, 403);

        $measurement->delete();

        return $this->success(null, 'Measurements deleted.');
    }

    /** @return array<string, mixed> */
    protected function validatedPayload(Request $request, ?CustomerMeasurement $existing = null): array
    {
        $data = $request->validate(
            CustomerMeasurement::apiValidationRules($existing !== null)
        );

        return CustomerMeasurement::normalizeApiPayload($data, $existing);
    }
}
