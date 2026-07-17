<?php

namespace App\Http\Controllers\Web;

use App\Models\CheckoutOrder;
use App\Models\Customer;
use App\Services\Checkout\CheckoutService;
use App\Services\Customer\CartService;
use App\Support\BookingMeasurementSupport;
use App\Support\WebMeasurementForm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use InvalidArgumentException;

class CheckoutController extends WebController
{
    public function __construct(
        protected CartService $cart,
        protected CheckoutService $checkout
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        if ($this->cart->itemsFor($customer)->isEmpty()) {
            return redirect()
                ->route('web.cart.index')
                ->with('info', 'Your cart is empty. Add items before checkout.');
        }

        $addresses = $customer->addresses()->orderByDesc('is_default')->orderByDesc('id')->get();
        $defaultAddress = $customer->defaultAddress();
        $measurementProfiles = $customer->measurements()->latest('id')->get();

        $selectedProfileId = (int) old('measurement_profile_id', $request->query('measurement_profile_id', 0));
        $measurement = $measurementProfiles->firstWhere('id', $selectedProfileId)
            ?? $measurementProfiles->first();

        $summary = $this->cart->summary($customer);

        $vendorShipments = $this->vendorShipmentsFromRequest($request, $summary['vendors'] ?? []);

        try {
            $preview = $this->checkout->preview($customer, [
                'rental_start_date' => old('rental_start_date'),
                'rental_end_date' => old('rental_end_date'),
                'vendor_shipments' => $vendorShipments,
            ]);
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('web.cart.index')
                ->with('error', $exception->getMessage());
        }

        return view('web.checkout.show', [
            'addresses' => $addresses,
            'defaultAddress' => $defaultAddress,
            'measurement' => $measurement,
            'measurementProfiles' => $measurementProfiles,
            'summary' => $summary,
            'preview' => $preview,
            'cartItems' => $this->cart->itemsFor($customer),
            'measurementValues' => WebMeasurementForm::valuesFromProfile($measurement),
            'measurementSections' => WebMeasurementForm::sections(),
        ]);
    }

    public function preview(Request $request): \Illuminate\Http\JsonResponse
    {
        /** @var \App\Models\Customer $customer */
        $customer = Auth::guard('customer')->user();

        if ($this->cart->itemsFor($customer)->isEmpty()) {
            return response()->json(['message' => 'Your cart is empty.'], 422);
        }

        $summary = $this->cart->summary($customer);
        $data = $request->validate([
            'rental_start_date' => ['required', 'date', 'after_or_equal:today'],
            'rental_end_date' => ['required', 'date', 'after_or_equal:rental_start_date'],
            'vendor_shipments' => ['nullable', 'array'],
            'vendor_shipments.*.vendor_id' => ['required_with:vendor_shipments', 'integer', 'exists:vendors,id'],
            'vendor_shipments.*.shipment_required' => ['nullable', 'boolean'],
        ]);

        $data['vendor_shipments'] = $this->normalizeVendorShipments(
            $data['vendor_shipments'] ?? $this->vendorShipmentsFromRequest($request, $summary['vendors'] ?? [])
        );

        try {
            return response()->json($this->checkout->preview($customer, $data));
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $data = $request->validate(array_merge([
            'delivery_address' => ['required', 'string', 'max:500'],
            'billing_address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:10'],
            'rental_start_date' => ['required', 'date', 'after_or_equal:today'],
            'rental_end_date' => ['required', 'date', 'after_or_equal:rental_start_date'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
            'address_id' => ['nullable', 'integer', 'exists:customer_addresses,id'],
            'measurement_profile_id' => ['nullable', 'integer'],
            'vendor_shipments' => ['nullable', 'array'],
            'vendor_shipments.*.vendor_id' => ['required_with:vendor_shipments', 'integer', 'exists:vendors,id'],
            'vendor_shipments.*.shipment_required' => ['nullable', 'boolean'],
        ], BookingMeasurementSupport::validationRules()));

        if ($request->filled('address_id')) {
            $address = $customer->addresses()->find($request->integer('address_id'));
            abort_unless($address, 403);

            $data['delivery_address'] = $address->fullAddress();
            $data['city'] = $address->city;
            $data['pincode'] = $address->pincode;
        }

        $data['vendor_shipments'] = $this->normalizeVendorShipments($data['vendor_shipments'] ?? []);

        try {
            $checkout = $this->checkout->createFromCart($customer, $data, $request);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('web.checkout.payment', $checkout)
            ->with('success', 'Checkout created. Complete payment to confirm your order.');
    }

    public function showOrder(CheckoutOrder $checkoutOrder): View|RedirectResponse
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();
        abort_unless($checkoutOrder->customer_id === $customer->id, 403);

        return redirect()->route('web.bookings.checkout.show', $checkoutOrder);
    }

    /**
     * @param  list<array<string, mixed>>  $vendors
     * @return list<array<string, mixed>>
     */
    protected function vendorShipmentsFromRequest(Request $request, array $vendors): array
    {
        $old = old('vendor_shipments');

        if (is_array($old) && $old !== []) {
            return $this->normalizeVendorShipments($old);
        }

        return collect($vendors)->map(fn (array $vendor) => [
            'vendor_id' => $vendor['vendor_id'],
            'shipment_required' => true,
        ])->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    protected function normalizeVendorShipments(array $rows): array
    {
        return collect($rows)
            ->filter(fn ($row) => isset($row['vendor_id']))
            ->map(fn ($row) => [
                'vendor_id' => (int) $row['vendor_id'],
                'shipment_required' => filter_var($row['shipment_required'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ])
            ->values()
            ->all();
    }
}
