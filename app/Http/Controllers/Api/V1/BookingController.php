<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\CheckoutOrder;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderReview;
use App\Models\PortfolioItem;
use App\Models\Vendor;
use App\Services\Booking\BookingPricingService;
use App\Services\Customer\CartService;
use App\Support\Api\CustomerApiPresenter;
use App\Support\OrderDispatchSupport;
use App\Support\Api\CustomerBookingTab;
use App\Support\BookingMeasurementSupport;
use App\Support\CodeGenerator;
use App\Support\StoresUploadedFiles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends ApiController
{
    public function __construct(
        protected CartService $cart
    ) {}

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
                ->with(['vendor', 'category', 'customer', 'dispute', 'review'])
                ->where('customer_id', $customer->id)
                ->whereNull('checkout_order_id'),
            $request->input('tab')
        )
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 10));

        $checkoutOrders = CheckoutOrder::query()
            ->where('customer_id', $customer->id)
            ->with(['subOrders.vendor', 'subOrders.category'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn (CheckoutOrder $checkout) => CustomerApiPresenter::checkoutOrderSummary($checkout))
            ->all();

        return $this->success([
            ...CustomerApiPresenter::paginator($orders, fn (Order $order) => CustomerApiPresenter::bookingDetail($order)),
            'checkout_orders' => $checkoutOrders,
        ]);
    }

    public function show(Request $request, Order $booking): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        abort_unless($booking->customer_id === $customer->id, 403);

        $booking->load(['customer', 'vendor', 'driver', 'category', 'dispute', 'review']);

        return $this->success(CustomerApiPresenter::bookingDetail($booking));
    }

    public function preview(Request $request, PortfolioItem $item): JsonResponse
    {
        abort_unless($item->isApprovedForCatalog(), 404);

        /** @var Customer $customer */
        $customer = $request->user();

        $options = [
            'shipment_required' => $request->boolean('shipment_required', true),
            'cart' => $this->cart->apiPayload($customer),
            'cart_item_status' => $this->cart->itemStatusForProduct($customer, $item->id),
        ];

        return $this->success(CustomerApiPresenter::bookingPreview($item, $customer, $options));
    }

    public function store(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        $data = $request->validate(array_merge([
            'portfolio_item_id' => ['required', 'integer', 'exists:portfolio_items,id'],
            'size' => ['nullable', 'string', 'max:10'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
            'delivery_address' => ['required', 'string', 'max:500'],
            'billing_address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:10'],
            'rental_start_date' => ['nullable', 'date'],
            'rental_end_date' => ['nullable', 'date', 'after_or_equal:rental_start_date'],
            'shipment_required' => ['nullable', 'boolean'],
            'reference_images' => ['nullable', 'array', 'max:5'],
            'reference_images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ], BookingMeasurementSupport::validationRules()));

        $item = PortfolioItem::query()
            ->with(['vendor', 'category'])
            ->findOrFail($data['portfolio_item_id']);

        abort_unless($item->status === 'approved', 422, 'This product is not available for booking.');
        abort_unless($item->vendor && $item->vendor->status === 'active', 422, 'Designer is not available.');

        $pricing = BookingPricingService::forPortfolioItem($item, [
            'shipment_required' => $request->boolean('shipment_required', true),
            'rental_days' => BookingPricingService::rentalDays(
                $data['rental_start_date'] ?? null,
                $data['rental_end_date'] ?? null,
            ),
        ]);

        $notes = trim((string) ($data['customer_notes'] ?? ''));
        $measurements = BookingMeasurementSupport::normalizeForOrder(
            $data,
            $customer->measurements()->latest('id')->first(),
        );

        $order = Order::query()->create([
            'order_number' => CodeGenerator::orderNumber(),
            'customer_id' => $customer->id,
            'vendor_id' => $item->vendor_id,
            'category_id' => $item->category_id,
            'portfolio_item_id' => $item->id,
            'subcategory_id' => $item->subcategory_id,
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
            'measure_height_cm' => $measurements['measure_height_cm'],
            'measure_chest_cm' => $measurements['measure_chest_cm'],
            'measure_waist_cm' => $measurements['measure_waist_cm'],
            'measurement_type' => $measurements['measurement_type'],
            'measure_extra' => $measurements['measure_extra'],
            'payment_status' => 'pending',
            'status' => 'new',
        ]);

        OrderDispatchSupport::preparePickupAddress($order);
        if (filled($order->pickup_address)) {
            $order->saveQuietly();
        }

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

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $booking->update([
            'status' => 'cancelled',
            'cancellation_reason' => $data['reason'],
        ]);

        return $this->success([
            'booking' => CustomerApiPresenter::bookingDetail($booking->fresh(['vendor', 'category', 'dispute', 'review'])),
        ], 'Booking cancelled.');
    }

    public function review(Request $request, Order $booking): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        abort_unless($booking->customer_id === $customer->id, 403);

        if ($booking->status !== 'delivered') {
            return $this->error('You can review this booking after it is delivered.', 422);
        }

        if ($booking->review()->exists()) {
            return $this->error('You have already reviewed this booking.', 422);
        }

        abort_unless($booking->vendor_id, 422, 'Vendor not found for this booking.');

        $data = $request->validate([
            'rating' => ['required', 'numeric', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $review = OrderReview::query()->create([
            'order_id' => $booking->id,
            'customer_id' => $customer->id,
            'vendor_id' => $booking->vendor_id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        $this->syncVendorRating($booking->vendor_id);

        return $this->success([
            'review' => CustomerApiPresenter::orderReview($review->load(['customer', 'order'])),
            'booking' => CustomerApiPresenter::bookingDetail($booking->fresh(['vendor', 'category', 'dispute', 'review'])),
        ], 'Review submitted.');
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

    protected function syncVendorRating(?int $vendorId): void
    {
        if (! $vendorId) {
            return;
        }

        $average = OrderReview::query()->where('vendor_id', $vendorId)->avg('rating');

        Vendor::query()->whereKey($vendorId)->update([
            'rating' => round((float) $average, 2),
        ]);
    }
}
