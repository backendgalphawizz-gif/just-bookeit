<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Customer;
use App\Models\CustomerMeasurement;
use App\Support\Api\CustomerApiPresenter;
use App\Support\WebMeasurementForm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeasurementController extends ApiController
{
    /**
     * Form schema for Women / Men / Kids measurement fields (matches website).
     * Optional ?type=women|men|kid|kids to return a single form.
     */
    public function forms(Request $request): JsonResponse
    {
        $type = $request->string('type')->toString();
        if ($type === 'kids') {
            $type = 'kid';
        }

        if ($type !== '' && ! in_array($type, CustomerMeasurement::TYPES, true)) {
            return $this->error('Invalid measurement type. Use women, men, or kid.', 422);
        }

        return $this->success(
            WebMeasurementForm::apiFormSchema($type !== '' ? $type : null)
        );
    }

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
