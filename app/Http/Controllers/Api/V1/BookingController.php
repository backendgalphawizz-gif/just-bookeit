<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PortfolioItem;
use App\Services\Booking\BookingPricingService;
use App\Support\Api\CustomerApiPresenter;
use App\Support\Api\CustomerBookingTab;
use App\Support\CodeGenerator;
use App\Support\StoresUploadedFiles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        $request->validate([
            'tab' => CustomerBookingTab::validationRule(),
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $orders = CustomerBookingTab::applyToQuery(
            Order::query()
                ->with(['vendor', 'category'])
                ->where('customer_id', $customer->id),
            $request->input('tab')
        )
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 10));

        return $this->success(
            CustomerApiPresenter::paginator($orders, fn (Order $order) => CustomerApiPresenter::bookingSummary($order))
        );
    }

    public function show(Request $request, Order $booking): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        abort_unless($booking->customer_id === $customer->id, 403);

        $booking->load(['customer', 'vendor', 'driver', 'category']);

        return $this->success(CustomerApiPresenter::bookingDetail($booking));
    }

    public function preview(Request $request, PortfolioItem $item): JsonResponse
    {
        abort_unless($item->isApprovedForCatalog(), 404);

        /** @var Customer|null $customer */
        $customer = $request->user();

        return $this->success(CustomerApiPresenter::bookingPreview($item, $customer, [
            'shipment_required' => $request->boolean('shipment_required', true),
        ]));
    }

    public function store(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        $data = $request->validate([
            'portfolio_item_id' => ['required', 'integer', 'exists:portfolio_items,id'],
            'size' => ['nullable', 'string', 'max:10'],
            'measurement_type' => ['nullable', 'in:women,men,kid'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
            'delivery_address' => ['required', 'string', 'max:500'],
            'billing_address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:10'],
            'rental_start_date' => ['nullable', 'date'],
            'rental_end_date' => ['nullable', 'date', 'after_or_equal:rental_start_date'],
            'shipment_required' => ['nullable', 'boolean'],
            'measure_height_cm' => ['nullable', 'integer', 'min:0', 'max:300'],
            'measure_chest_cm' => ['nullable', 'integer', 'min:0', 'max:300'],
            'measure_waist_cm' => ['nullable', 'integer', 'min:0', 'max:300'],
            'reference_images' => ['nullable', 'array', 'max:5'],
            'reference_images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ]);

        $item = PortfolioItem::query()
            ->with(['vendor', 'category'])
            ->findOrFail($data['portfolio_item_id']);

        abort_unless($item->isApprovedForCatalog(), 422);
        abort_unless($item->vendor && $item->vendor->status === 'active', 422, 'Designer is not available.');

        $pricing = BookingPricingService::forPortfolioItem($item, [
            'shipment_required' => $request->boolean('shipment_required', true),
        ]);

        $notes = trim((string) ($data['customer_notes'] ?? ''));
        if (! empty($data['measurement_type'])) {
            $notes = trim('Measurement: '.ucfirst($data['measurement_type']).($notes ? "\n".$notes : ''));
        }

        $order = Order::query()->create([
            'order_number' => CodeGenerator::orderNumber(),
            'customer_id' => $customer->id,
            'vendor_id' => $item->vendor_id,
            'category_id' => $item->category_id,
            'order_type' => 'rental',
            'item_title' => $item->title,
            'item_description' => $item->description,
            'item_image_path' => $item->image_url,
            'size' => $data['size'] ?? null,
            'quantity' => 1,
            'rental_start_date' => $data['rental_start_date'] ?? null,
            'rental_end_date' => $data['rental_end_date'] ?? null,
            'delivery_address' => $data['delivery_address'],
            'billing_address' => $data['billing_address'] ?? $data['delivery_address'],
            'city' => $data['city'] ?? $customer->city,
            'pincode' => $data['pincode'] ?? null,
            'amount' => $pricing['subtotal'],
            'delivery_fee' => $pricing['shipping_fee'],
            'tax_amount' => $pricing['tax_amount'],
            'customer_notes' => $notes !== '' ? $notes : null,
            'measure_height_cm' => $data['measure_height_cm'] ?? null,
            'measure_chest_cm' => $data['measure_chest_cm'] ?? null,
            'measure_waist_cm' => $data['measure_waist_cm'] ?? null,
            'payment_status' => 'pending',
            'status' => 'new',
        ]);

        if ($request->hasFile('reference_images')) {
            $paths = [];
            foreach ($request->file('reference_images') as $file) {
                $paths[] = StoresUploadedFiles::store($file, 'orders/reference-images');
            }
            $order->update(['reference_image_paths' => $paths]);
        }

        if ($customer->city === null && ! empty($data['city'])) {
            $customer->update(['city' => $data['city']]);
        }

        $this->syncCustomerOrderCount($customer->id);

        $order->load(['vendor', 'category', 'customer']);

        return $this->success([
            'booking' => CustomerApiPresenter::bookingDetail($order),
            'payment_summary' => BookingPricingService::fromOrder($order),
        ], 'Booking created. Proceed to payment.', 201);
    }

    public function cancel(Request $request, Order $booking): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        abort_unless($booking->customer_id === $customer->id, 403);

        if (! in_array($booking->status, ['new', 'pending_acceptance'], true)) {
            return $this->error('This booking can no longer be cancelled.', 422);
        }

        $booking->update(['status' => 'cancelled']);

        return $this->success([
            'booking' => CustomerApiPresenter::bookingSummary($booking->fresh(['vendor', 'category'])),
        ], 'Booking cancelled.');
    }

    public function addresses(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        $saved = $customer->addresses()->orderByDesc('is_default')->orderByDesc('id')->get();

        if ($saved->isNotEmpty()) {
            return $this->success([
                'items' => $saved->map(fn ($address) => CustomerApiPresenter::savedAddress($address))->values()->all(),
            ]);
        }

        $addresses = $customer->orders()
            ->whereNotNull('delivery_address')
            ->latest('id')
            ->limit(10)
            ->get()
            ->unique(fn (Order $order) => $order->delivery_address.'|'.$order->pincode)
            ->values()
            ->map(fn (Order $order, int $index) => CustomerApiPresenter::addressFromOrder($order, $index === 0 ? 'Home' : 'Other'))
            ->all();

        if ($addresses === [] && $default = CustomerApiPresenter::customerAddress($customer)) {
            $addresses = [$default];
        }

        return $this->success(['items' => $addresses]);
    }

    protected function syncCustomerOrderCount(int $customerId): void
    {
        $count = Order::query()->where('customer_id', $customerId)->count();
        Customer::query()->whereKey($customerId)->update(['total_orders' => $count]);
    }
}
