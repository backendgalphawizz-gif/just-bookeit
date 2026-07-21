<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\CheckoutOrder;
use App\Models\Customer;
use App\Services\Checkout\CheckoutService;
use App\Support\Api\CustomerApiPresenter;
use App\Support\BookingMeasurementSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class CheckoutController extends ApiController
{
    public function __construct(
        protected CheckoutService $checkout
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $checkouts = CheckoutOrder::query()
            ->where('customer_id', $customer->id)
            ->with(['subOrders.vendor', 'subOrders.category'])
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 10));

        return $this->success(
            CustomerApiPresenter::paginator($checkouts, fn (CheckoutOrder $checkout) => CustomerApiPresenter::checkoutOrderDetail($checkout))
        );
    }

    public function preview(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        $data = $request->validate(array_merge([
            'rental_start_date' => ['nullable', 'date'],
            'rental_end_date' => ['nullable', 'date', 'after_or_equal:rental_start_date'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'items' => ['nullable'],
            'items_json' => ['nullable'],
            'vendor_shipments' => ['nullable', 'array'],
            'vendor_shipments.*.vendor_id' => ['required_with:vendor_shipments', 'integer', 'exists:vendors,id'],
            'vendor_shipments.*.shipment_required' => ['nullable', 'boolean'],
        ], BookingMeasurementSupport::checkoutValidationRules()));

        foreach (['rental_start_date', 'rental_end_date', 'start_date', 'end_date', 'items_json'] as $key) {
            if ((! array_key_exists($key, $data) || blank($data[$key] ?? null)) && $request->filled($key)) {
                $data[$key] = $request->input($key);
            }
        }

        try {
            return $this->success($this->checkout->preview($customer, $data, true, $request));
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function store(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        $data = $request->validate(array_merge([
            'delivery_address' => ['required', 'string', 'max:500'],
            'billing_address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:10'],
            'rental_start_date' => ['nullable', 'date'],
            'rental_end_date' => ['nullable', 'date', 'after_or_equal:rental_start_date'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'event_date' => ['nullable', 'date'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
            'measurement_id' => ['nullable', 'integer'],
            'measurement_profile_id' => ['nullable', 'integer'],
            'items' => ['nullable'],
            'items_json' => ['nullable'],
            'vendor_shipments' => ['nullable', 'array'],
            'vendor_shipments.*.vendor_id' => ['required_with:vendor_shipments', 'integer', 'exists:vendors,id'],
            'vendor_shipments.*.shipment_required' => ['nullable', 'boolean'],
        ], BookingMeasurementSupport::checkoutValidationRules()));

        if ($data['measurement_id'] ?? null) {
            $data['measurement_profile_id'] = $data['measurement_id'];
        }

        foreach (['rental_start_date', 'rental_end_date', 'start_date', 'end_date', 'items_json'] as $key) {
            if ((! array_key_exists($key, $data) || blank($data[$key] ?? null)) && $request->filled($key)) {
                $data[$key] = $request->input($key);
            }
        }

        try {
            $checkout = $this->checkout->createFromCart($customer, $data, $request);

            return $this->success([
                'checkout_order' => CustomerApiPresenter::checkoutOrderDetail($checkout),
            ], 'Checkout created. Proceed to payment.', 201);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function show(Request $request, string $checkoutOrder): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        $model = CheckoutOrder::query()
            ->where('customer_id', $customer->id)
            ->where(function ($query) use ($checkoutOrder) {
                $query->where('order_number', $checkoutOrder);
                if (ctype_digit($checkoutOrder)) {
                    $query->orWhere('id', (int) $checkoutOrder);
                }
            })
            ->first();

        abort_unless($model, 404, 'Checkout order not found.');

        $model->load(['subOrders.vendor', 'subOrders.category', 'subOrders.driver', 'subOrders.orderItems', 'subOrders.review', 'refunds.histories']);

        return $this->success(CustomerApiPresenter::checkoutOrderDetail($model));
    }
}
