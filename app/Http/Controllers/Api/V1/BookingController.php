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
use App\Services\Checkout\CheckoutService;
use App\Services\Customer\CartService;
use App\Support\Api\CustomerApiPresenter;
use App\Support\OrderDispatchSupport;
use App\Support\Api\CustomerBookingTab;
use App\Support\BookingMeasurementSupport;
use App\Support\CodeGenerator;
use App\Support\StoresUploadedFiles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class BookingController extends ApiController
{
    public function __construct(
        protected CartService $cart,
        protected CheckoutService $checkout
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

        $tab = $request->input('tab');
        $categorySlug = CustomerBookingTab::categorySlug($tab);
        $perPage = $request->integer('per_page', 10);
        $page = max(1, $request->integer('page', 1));

        $standaloneQuery = Order::query()
            ->with(['vendor', 'category', 'customer', 'dispute', 'review', 'orderItems'])
            ->where('customer_id', $customer->id)
            ->whereNull('checkout_order_id');

        if ($categorySlug) {
            $standaloneQuery = CustomerBookingTab::applyToQuery($standaloneQuery, $tab);
        }

        $checkoutQuery = CheckoutOrder::query()
            ->with([
                'subOrders.vendor',
                'subOrders.category',
                'subOrders.orderItems.portfolioItem.category',
            ])
            ->where('customer_id', $customer->id);

        if ($categorySlug) {
            $checkoutQuery->whereHas('subOrders.category', fn ($q) => $q->where('slug', $categorySlug));
        }

        $entries = $standaloneQuery->get()
            ->map(fn (Order $order) => [
                'sort_at' => $order->created_at,
                'payload' => CustomerApiPresenter::bookingDetail($order),
            ])
            ->concat(
                $checkoutQuery->get()->map(fn (CheckoutOrder $checkout) => [
                    'sort_at' => $checkout->created_at,
                    'payload' => CustomerApiPresenter::checkoutOrderSummary($checkout),
                ])
            )
            ->sortByDesc(fn (array $row) => $row['sort_at']?->timestamp ?? 0)
            ->values()
            ->map(fn (array $row) => $row['payload']);

        $paginator = new LengthAwarePaginator(
            $entries->slice(($page - 1) * $perPage, $perPage)->values(),
            $entries->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return $this->success(
            CustomerApiPresenter::paginator($paginator, fn (array $item) => $item)
        );
    }

    public function show(Request $request, string $booking): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        // Multi-vendor checkouts appear in booking history with type=checkout_order.
        // Accept numeric id OR order_number (e.g. JB260708090).
        $checkout = $this->findCustomerCheckout($customer->id, $booking);
        if ($checkout) {
            return $this->success(CustomerApiPresenter::checkoutOrderDetail($checkout));
        }

        $order = $this->findCustomerOrder($customer->id, $booking);
        abort_unless($order, 404, 'Booking not found.');

        $order->load(['customer', 'vendor', 'driver', 'category', 'dispute', 'review', 'orderItems']);

        return $this->success(CustomerApiPresenter::bookingDetail($order));
    }

    public function preview(Request $request, PortfolioItem $item): JsonResponse
    {
        abort_unless($item->isApprovedForCatalog(), 404);

        /** @var Customer $customer */
        $customer = $request->user();

        $options = [
            'shipment_required' => $request->boolean('shipment_required', true),
            'cart' => $this->cart->apiPayload($customer, [
                'shipment_required' => $request->boolean('shipment_required', true),
            ]),
            'cart_item_status' => $this->cart->itemStatusForProduct($customer, $item->id),
        ];

        return $this->success(CustomerApiPresenter::bookingPreview($item, $customer, $options));
    }

    public function store(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        if ($request->filled('items') || $request->filled('items_json')) {
            return $this->storeMultiItemCheckout($request, $customer);
        }

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
            'event_date' => ['nullable', 'date'],
            'shipment_required' => ['nullable', 'boolean'],
            'measurement_id' => ['nullable', 'string'],
            'reference_images' => ['nullable', 'array', 'max:5'],
            'reference_images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ], BookingMeasurementSupport::checkoutValidationRules()));

        $item = PortfolioItem::query()
            ->with(['vendor', 'category'])
            ->findOrFail($data['portfolio_item_id']);

        abort_unless($item->status === 'approved', 422, 'This product is not available for booking.');
        abort_unless($item->vendor && $item->vendor->status === 'active', 422, 'Designer is not available.');

        if ($item->requiresRentalPeriod() && (empty($data['rental_start_date']) || empty($data['rental_end_date']))) {
            return $this->error('Rental start and end dates are required for this product.', 422);
        }

        // Fashion designer: store rental dates only when the app actually sends both.
        $rentalStart = $data['rental_start_date'] ?? null;
        $rentalEnd = $data['rental_end_date'] ?? null;
        if (! $item->requiresRentalPeriod() && (! $rentalStart || ! $rentalEnd)) {
            $rentalStart = null;
            $rentalEnd = null;
        }

        $pricing = BookingPricingService::forPortfolioItem($item, [
            'shipment_required' => $request->boolean('shipment_required', true),
            'rental_days' => BookingPricingService::rentalDays($rentalStart, $rentalEnd),
            'requires_rental_period' => $item->requiresRentalPeriod(),
        ]);

        $notes = trim((string) ($data['customer_notes'] ?? ''));
        $profile = BookingMeasurementSupport::resolveProfile($customer, $data);

        if (! empty($data['measurement_id']) && ! $profile) {
            return $this->error('The selected measurement profile was not found.', 422);
        }

        $measurements = BookingMeasurementSupport::normalizeFromProfileSelection($data, $profile);

        $order = Order::query()->create([
            'order_number' => CodeGenerator::orderNumber(),
            'customer_id' => $customer->id,
            'vendor_id' => $item->vendor_id,
            'category_id' => $item->category_id,
            'portfolio_item_id' => $item->id,
            'subcategory_id' => $item->subcategory_id,
            'order_type' => $item->requiresRentalPeriod() ? 'rental' : 'sale',
            'item_title' => $item->title,
            'item_description' => $item->description,
            'item_image_path' => $item->image_url,
            'size' => $data['size'] ?? null,
            'quantity' => 1,
            'rental_start_date' => $rentalStart,
            'rental_end_date' => $rentalEnd,
            'event_date' => $data['event_date'] ?? null,
            'delivery_address' => $data['delivery_address'],
            'billing_address' => $data['billing_address'] ?? $data['delivery_address'],
            'city' => $data['city'] ?? $customer->city,
            'pincode' => $data['pincode'] ?? null,
            'amount' => $pricing['subtotal'],
            'delivery_fee' => $pricing['shipping_fee'],
            'tax_amount' => $pricing['tax_amount'],
            'advance_amount' => $pricing['advance_amount'] ?? 0,
            'amount_paid' => 0,
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

    protected function storeMultiItemCheckout(Request $request, Customer $customer): JsonResponse
    {
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
            'shipment_required' => ['nullable', 'boolean'],
            'vendor_shipments' => ['nullable', 'array'],
            'vendor_shipments.*.vendor_id' => ['required_with:vendor_shipments', 'integer', 'exists:vendors,id'],
            'vendor_shipments.*.shipment_required' => ['nullable', 'boolean'],
            // Prefer items_json when uploading items[N][reference_images][] — a form field named
            // "items" is overwritten by PHP when file fields use the items[...] prefix.
            'items' => ['nullable'],
            'items_json' => ['nullable'], // JSON string or already-decoded array
        ], BookingMeasurementSupport::checkoutValidationRules()));

        if (! $request->filled('items') && ! $request->filled('items_json')) {
            return $this->error('Send items_json (recommended) or items with the cart line payload.', 422);
        }

        // Multipart clients sometimes drop validated nullable dates; re-merge from the raw request.
        foreach (['rental_start_date', 'rental_end_date', 'start_date', 'end_date', 'event_date', 'items_json'] as $key) {
            if ((! array_key_exists($key, $data) || blank($data[$key] ?? null)) && $request->filled($key)) {
                $data[$key] = $request->input($key);
            }
        }

        if ($data['measurement_id'] ?? null) {
            $data['measurement_profile_id'] = $data['measurement_id'];
        }

        if ($this->cart->itemsFor($customer)->isEmpty()) {
            return $this->error('Your cart is empty. Add items before checkout.', 422);
        }

        try {
            $checkout = $this->checkout->createFromCart($customer, $data, $request);

            return $this->success([
                'checkout_order' => CustomerApiPresenter::checkoutOrderDetail($checkout),
                'booking_type' => 'multi_vendor_checkout',
            ], 'Checkout created. Proceed to payment.', 200);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }
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

    protected function findCustomerCheckout(int $customerId, string $key): ?CheckoutOrder
    {
        return CheckoutOrder::query()
            ->where('customer_id', $customerId)
            ->where(function ($query) use ($key) {
                $query->where('order_number', $key);
                if (ctype_digit($key)) {
                    $query->orWhere('id', (int) $key);
                }
            })
            ->first();
    }

    protected function findCustomerOrder(int $customerId, string $key): ?Order
    {
        return Order::query()
            ->where('customer_id', $customerId)
            ->where(function ($query) use ($key) {
                $query->where('order_number', $key)
                    ->orWhere('sub_order_number', $key);
                if (ctype_digit($key)) {
                    $query->orWhere('id', (int) $key);
                }
            })
            ->first();
    }
}
