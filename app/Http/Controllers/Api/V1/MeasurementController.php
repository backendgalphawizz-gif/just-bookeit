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
            'items' => $profiles->map(fn (CustomerMeasurement $profile) => CustomerApiPresenter::measurementSummary($profile))->values()->all(),
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

        $data = $this->validatedPayload($request);
        $profile = $customer->measurements()->create($data);

        return $this->success([
            'measurement' => CustomerApiPresenter::measurementDetail($profile),
        ], 'Measurements saved.', 201);
    }

    public function update(Request $request, CustomerMeasurement $measurement): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        abort_unless($measurement->customer_id === $customer->id, 403);

        $measurement->update($this->validatedPayload($request, partial: true));

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
    protected function validatedPayload(Request $request, bool $partial = false): array
    {
        $extraRules = [];
        foreach (CustomerMeasurement::EXTRA_FIELDS as $field) {
            $extraRules["extra_measurements.{$field}"] = ['nullable', 'string', 'max:50'];
        }

        $data = $request->validate(array_merge([
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'measurement_type' => ['nullable', 'in:women,men,kid'],
            'height_cm' => ['nullable', 'integer', 'min:0', 'max:300'],
            'chest_cm' => ['nullable', 'integer', 'min:0', 'max:300'],
            'waist_cm' => ['nullable', 'integer', 'min:0', 'max:300'],
            'extra_measurements' => ['nullable', 'array'],
        ], $extraRules));

        if (isset($data['extra_measurements'])) {
            $data['extra_measurements'] = collect($data['extra_measurements'])
                ->only(CustomerMeasurement::EXTRA_FIELDS)
                ->filter(fn ($value) => $value !== null && $value !== '')
                ->all();
        }

        return $data;
    }
}
