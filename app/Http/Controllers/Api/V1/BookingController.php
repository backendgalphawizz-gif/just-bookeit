<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\CheckoutOrder;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReview;
use App\Models\PortfolioItem;
use App\Models\Vendor;
use App\Services\Booking\BookingPaymentService;
use App\Services\Booking\BookingPricingService;
use App\Services\Checkout\CheckoutService;
use App\Services\Customer\CartService;
use App\Support\Api\CustomerApiPresenter;
use App\Support\Api\CustomerBookingTab;
use App\Support\Api\VendorBookingStatus;
use App\Support\OrderDispatchSupport;
use App\Support\BookingMeasurementSupport;
use App\Support\CheckoutItemPayloadSupport;
use App\Support\CodeGenerator;
use App\Support\RazorpayPaymentSupport;
use App\Support\StoresUploadedFiles;
use App\Models\PlatformSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

class BookingController extends ApiController
{
    public function __construct(
        protected CartService $cart,
        protected CheckoutService $checkout,
        protected BookingPaymentService $payments
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        $request->validate([
            'tab' => CustomerBookingTab::validationRule(),
            'status' => ['nullable', 'string', 'max:50'],
            'item_status' => ['nullable', 'string', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $tab = $request->input('tab');
        $categorySlug = CustomerBookingTab::categorySlug($tab);
        $bookingStatuses = $this->normalizeStatusFilter($request->input('status'));
        $itemStatuses = $this->normalizeStatusFilter($request->input('item_status'));
        $perPage = $request->integer('per_page', 10);
        $page = max(1, $request->integer('page', 1));

        $standaloneQuery = Order::query()
            ->with(['vendor', 'category', 'customer', 'dispute', 'review', 'driver', 'orderItems.driver'])
            ->where('customer_id', $customer->id)
            ->whereNull('checkout_order_id');

        if ($categorySlug) {
            $standaloneQuery = CustomerBookingTab::applyToQuery($standaloneQuery, $tab);
        }

        if ($bookingStatuses !== null) {
            $standaloneQuery->whereIn('status', $bookingStatuses);
        }

        if ($itemStatuses !== null) {
            $standaloneQuery->where(function ($q) use ($itemStatuses) {
                $q->whereHas('orderItems', fn ($items) => $items->whereIn('status', $itemStatuses))
                    // Legacy bookings without line items: fall back to order status.
                    ->orWhere(function ($legacy) use ($itemStatuses) {
                        $legacy->whereDoesntHave('orderItems')
                            ->whereIn('status', $itemStatuses);
                    });
            });
        }

        $checkoutQuery = CheckoutOrder::query()
            ->with([
                'subOrders.vendor',
                'subOrders.category',
                'subOrders.driver',
                'subOrders.orderItems.driver',
                'subOrders.orderItems.portfolioItem.category',
            ])
            ->where('customer_id', $customer->id);

        if ($categorySlug) {
            $checkoutQuery->whereHas('subOrders.category', fn ($q) => $q->where('slug', $categorySlug));
        }

        if ($bookingStatuses !== null) {
            $checkoutQuery->where(function ($q) use ($bookingStatuses) {
                $q->whereIn('status', $bookingStatuses)
                    ->orWhereHas('subOrders', fn ($sub) => $sub->whereIn('status', $bookingStatuses));
            });
        }

        if ($itemStatuses !== null) {
            $checkoutQuery->whereHas('subOrders.orderItems', fn ($items) => $items->whereIn('status', $itemStatuses));
        }

        $entries = $standaloneQuery->get()
            ->map(fn (Order $order) => [
                'sort_at' => $order->created_at,
                'payload' => CustomerApiPresenter::bookingDetail(
                    $order,
                    itemStatusFilter: $itemStatuses
                ),
            ])
            ->concat(
                $checkoutQuery->get()->map(fn (CheckoutOrder $checkout) => [
                    'sort_at' => $checkout->created_at,
                    'payload' => CustomerApiPresenter::checkoutOrderSummary(
                        $checkout,
                        itemStatusFilter: $itemStatuses,
                        bookingStatusFilter: $bookingStatuses
                    ),
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

    /**
     * Normalize API status aliases into DB status list (or null if empty/invalid).
     *
     * @return list<string>|null
     */
    protected function normalizeStatusFilter(mixed $raw): ?array
    {
        if ($raw === null || trim((string) $raw) === '') {
            return null;
        }

        $key = strtolower(trim((string) $raw));
        $fromTab = VendorBookingStatus::statusesForTab($key);
        if ($fromTab !== null) {
            return $fromTab;
        }

        $normalized = VendorBookingStatus::normalizeInput($key);
        if (in_array($normalized, Order::STATUSES, true) || in_array($normalized, \App\Models\OrderItem::STATUSES, true)) {
            return [$normalized];
        }

        return null;
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

        $order->load([
            'customer.measurements',
            'vendor',
            'driver',
            'category',
            'dispute',
            'review.customer',
            'orderItems.driver',
            'checkoutOrder',
            'refunds',
            'refund.histories',
        ]);

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
            // Accept any variant size/color string the app sends (matches portfolio_item_variants).
            'size' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:100'],
            'portfolio_item_variant_id' => ['nullable', 'integer', 'exists:portfolio_item_variants,id'],
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
            // Optional: COD can be confirmed at place-order time and sent straight to the vendor.
            'payment_method' => ['nullable', 'string', RazorpayPaymentSupport::allowedMethodRule()],
        ], BookingMeasurementSupport::checkoutValidationRules()));

        $item = PortfolioItem::query()
            ->with(['vendor', 'category', 'variants'])
            ->findOrFail($data['portfolio_item_id']);

        abort_unless($item->status === 'approved', 422, 'This product is not available for booking.');
        abort_unless($item->vendor && $item->vendor->status === 'active', 422, 'Designer is not available.');

        $variant = null;
        if (! empty($data['portfolio_item_variant_id'])) {
            $variant = $item->findVariant((int) $data['portfolio_item_variant_id']);
            if (! $variant) {
                return $this->error('The selected size/color variant is not available for this product.', 422);
            }
        }

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
            'variant' => $variant,
        ]);

        $notes = trim((string) ($data['customer_notes'] ?? ''));
        $profile = BookingMeasurementSupport::resolveProfile($customer, $data);

        if (! empty($data['measurement_id']) && ! $profile) {
            return $this->error('The selected measurement profile was not found.', 422);
        }

        $measurements = BookingMeasurementSupport::normalizeFromProfileSelection($data, $profile);

        $size = filled($data['size'] ?? null)
            ? trim((string) $data['size'])
            : ($variant?->size ?: null);
        $color = filled($data['color'] ?? null)
            ? trim((string) $data['color'])
            : ($variant?->color ?: null);

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
            'size' => $size !== '' ? $size : null,
            'color' => $color !== '' ? $color : null,
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

        $paymentSummary = BookingPricingService::fromOrder($order);
        $message = 'Booking created. Proceed to payment.';

        if (($data['payment_method'] ?? null) === 'cod') {
            if (! (bool) PlatformSetting::get('enable_cod', false)) {
                return $this->error('Cash on delivery is not available.', 422);
            }

            try {
                $order = $this->payments->payOrder($order, 'cod');
                $paymentSummary = $this->payments->summaryForOrder($order);
                $message = 'Booking placed with cash on delivery. Sent to the designer.';
            } catch (InvalidArgumentException $e) {
                return $this->error($e->getMessage(), 422);
            }
        }

        return $this->success([
            'booking' => CustomerApiPresenter::bookingDetail($order->fresh(['vendor', 'category', 'customer', 'dispute', 'review', 'orderItems'])),
            'payment_summary' => $paymentSummary,
        ], $message, 201);
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
            'cart_items' => ['nullable'],
            'line_items' => ['nullable'],
            'payment_method' => ['nullable', 'string', RazorpayPaymentSupport::allowedMethodRule()],
        ], BookingMeasurementSupport::checkoutValidationRules()));

        if (! $request->filled('items') && ! $request->filled('items_json') && ! $request->filled('cart_items') && ! $request->filled('line_items')) {
            return $this->error('Send items_json (recommended) or items with the cart line payload.', 422);
        }

        // Multipart clients sometimes drop validated nullable dates; re-merge from the raw request.
        foreach (['rental_start_date', 'rental_end_date', 'start_date', 'end_date', 'event_date', 'items_json', 'cart_items', 'line_items'] as $key) {
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
            $message = 'Checkout created. Proceed to payment.';

            if (($data['payment_method'] ?? null) === 'cod') {
                if (! (bool) PlatformSetting::get('enable_cod', false)) {
                    return $this->error('Cash on delivery is not available.', 422);
                }

                $checkout = $this->payments->payCheckout($checkout, 'cod');
                $message = 'Order placed with cash on delivery. Sent to the designers.';
            }

            return $this->success([
                'checkout_order' => CustomerApiPresenter::checkoutOrderDetail($checkout->fresh([
                    'subOrders.vendor',
                    'subOrders.category',
                    'subOrders.orderItems',
                ])),
                'booking_type' => 'multi_vendor_checkout',
                'payment_summary' => $this->payments->summaryForCheckout($checkout),
            ], $message, 200);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function cancel(Request $request, Order $booking): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        abort_unless($booking->customer_id === $customer->id, 403);

        if (! in_array($booking->status, ['new', 'pending_acceptance', 'accepted'], true)) {
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

    /**
     * Diagram: User receives order → rental_active (rentals) or acknowledge delivery (designer).
     */
    public function confirmReceived(Request $request, Order $booking): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        abort_unless($booking->customer_id === $customer->id, 403);

        try {
            $updated = app(\App\Services\Booking\BookingLifecycleService::class)->confirmReceived($booking);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success([
            'booking' => CustomerApiPresenter::bookingDetail($updated->load(['vendor', 'category', 'dispute', 'review', 'driver'])),
        ], $updated->status === 'rental_active'
            ? 'Order received. Rental is now active.'
            : 'Order received.');
    }

    /**
     * Request pickup so rented dress/jewellery is returned to the vendor.
     * This is product return — not a dispute.
     */
    public function requestReturn(Request $request, Order $booking): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        abort_unless($booking->customer_id === $customer->id, 403);

        $data = $request->validate([
            'item_id' => ['nullable', 'integer', 'exists:order_items,id'],
        ]);

        $item = null;
        if (! empty($data['item_id'])) {
            $item = OrderItem::query()->findOrFail($data['item_id']);
        }

        try {
            $updated = app(\App\Services\Booking\BookingLifecycleService::class)
                ->requestReturn($booking, $item);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success([
            'booking' => CustomerApiPresenter::bookingDetail($updated->load([
                'vendor',
                'category',
                'dispute',
                'review',
                'driver',
                'orderItems.driver',
            ])),
            'return_type' => 'rental_product_return',
            'return_note' => 'Product return to vendor (rented dress/jewellery). This is not a dispute.',
        ], 'Return pickup requested for rented product(s). Awaiting driver assignment.');
    }

    /**
     * Diagram: Need rework (designer fitting / issue during rental).
     */
    public function requestRework(Request $request, Order $booking): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        abort_unless($booking->customer_id === $customer->id, 403);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'min:5', 'max:1000'],
        ]);

        try {
            $updated = app(\App\Services\Booking\BookingLifecycleService::class)
                ->requestRework($booking, $data['reason'] ?? null);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success([
            'booking' => CustomerApiPresenter::bookingDetail($updated->load(['vendor', 'category', 'dispute', 'review', 'driver'])),
        ], 'Rework requested.');
    }

    public function review(Request $request, Order $booking): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        abort_unless($booking->customer_id === $customer->id, 403);

        if (! in_array($booking->status, ['delivered', 'rental_active', 'returned', 're_delivered', 'completed'], true)) {
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
