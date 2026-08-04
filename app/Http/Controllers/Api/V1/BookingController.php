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
            'pincode' => \App\Support\AdminValidationRules::pincodeRules(),
            'rental_start_date' => ['nullable', 'date'],
            'rental_end_date' => ['nullable', 'date', 'after_or_equal:rental_start_date'],
            'event_date' => ['nullable', 'date'],
            'shipment_required' => ['nullable', 'boolean'],
            'measurement_id' => ['nullable', 'string'],
            'reference_images' => ['nullable', 'array', 'max:5'],
            'reference_images.*' => \App\Support\MediaUploadSupport::imageRules(4096),
            // Optional: COD or online payment can be confirmed at place-order time.
            'payment_method' => ['nullable', 'string', RazorpayPaymentSupport::allowedMethodRule()],
            'razorpay_order_id' => ['nullable', 'string', 'max:100'],
            'razorpay_payment_id' => ['nullable', 'string', 'max:100'],
            'razorpay_signature' => ['nullable', 'string', 'max:255'],
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
        $paymentMeta = null;

        $wantsOnlinePay = filled($data['razorpay_payment_id'] ?? null)
            || RazorpayPaymentSupport::isOnlineMethod((string) ($data['payment_method'] ?? ''));

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
        } elseif ($wantsOnlinePay) {
            try {
                $result = $this->finalizeOrderOnlinePayment($order, $data);
                $order = $result['order'];
                $paymentSummary = $result['payment_summary'];
                $paymentMeta = $result['payment'];
                $message = $result['message'];
            } catch (InvalidArgumentException|\RuntimeException $e) {
                return $this->error($e->getMessage(), 422);
            }
        }

        return $this->success([
            'booking' => CustomerApiPresenter::bookingDetail($order->fresh(['vendor', 'category', 'customer', 'dispute', 'review', 'orderItems'])),
            'payment_summary' => $paymentSummary,
            'amount_paid' => (float) ($paymentSummary['amount_paid'] ?? $order->amount_paid ?? 0),
            'payment' => $paymentMeta,
        ], $message, 201);
    }

    protected function storeMultiItemCheckout(Request $request, Customer $customer): JsonResponse
    {
        $data = $request->validate(array_merge([
            'delivery_address' => ['required', 'string', 'max:500'],
            'billing_address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'pincode' => \App\Support\AdminValidationRules::pincodeRules(),
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
            'razorpay_order_id' => ['nullable', 'string', 'max:100'],
            'razorpay_payment_id' => ['nullable', 'string', 'max:100'],
            'razorpay_signature' => ['nullable', 'string', 'max:255'],
        ], BookingMeasurementSupport::checkoutValidationRules()));

        // Multipart + file fields named items[N][...] often wipe the text field "items".
        // Treat presence of items_json / cart / files under items[*] as enough to continue.
        $hasItemsPayload = $request->filled('items')
            || $request->filled('items_json')
            || $request->filled('cart_items')
            || $request->filled('line_items')
            || is_array($request->file('items'));

        if (! $hasItemsPayload) {
            return $this->error('Send items_json (recommended) or items with the cart line payload.', 422);
        }

        // Multipart clients sometimes drop validated nullable dates; re-merge from the raw request.
        foreach (['rental_start_date', 'rental_end_date', 'start_date', 'end_date', 'event_date', 'items_json', 'cart_items', 'line_items', 'razorpay_order_id', 'razorpay_payment_id', 'razorpay_signature'] as $key) {
            if ((! array_key_exists($key, $data) || blank($data[$key] ?? null)) && $request->filled($key)) {
                $data[$key] = $request->input($key);
            }
        }

        if ($data['measurement_id'] ?? null) {
            $data['measurement_profile_id'] = $data['measurement_id'];
        }

        $cartItems = $this->cart->itemsFor($customer);
        if ($cartItems->isEmpty()) {
            return $this->error('Your cart is empty. Add items before checkout.', 422);
        }

        // If PHP clobbered items JSON with image files, rebuild lines from the cart + top-level dates.
        $data = CheckoutItemPayloadSupport::recoverItemsFromCartWhenClobbered(
            $data,
            $request,
            $cartItems
        );

        try {
            $checkout = $this->checkout->createFromCart($customer, $data, $request);
            $message = 'Checkout created. Proceed to payment.';
            $paymentSummary = $this->payments->summaryForCheckout($checkout);
            $paymentMeta = null;

            $wantsOnlinePay = filled($data['razorpay_payment_id'] ?? null)
                || RazorpayPaymentSupport::isOnlineMethod((string) ($data['payment_method'] ?? ''));

            if (($data['payment_method'] ?? null) === 'cod') {
                if (! (bool) PlatformSetting::get('enable_cod', false)) {
                    return $this->error('Cash on delivery is not available.', 422);
                }

                $checkout = $this->payments->payCheckout($checkout, 'cod');
                $paymentSummary = $this->payments->summaryForCheckout($checkout);
                $message = 'Order placed with cash on delivery. Sent to the designers.';
            } elseif ($wantsOnlinePay) {
                $result = $this->finalizeCheckoutOnlinePayment($checkout, $data);
                $checkout = $result['checkout'];
                $paymentSummary = $result['payment_summary'];
                $paymentMeta = $result['payment'];
                $message = $result['message'];
            }

            return $this->success([
                'checkout_order' => CustomerApiPresenter::checkoutOrderDetail($checkout->fresh([
                    'subOrders.vendor',
                    'subOrders.category',
                    'subOrders.orderItems',
                ])),
                'booking_type' => 'multi_vendor_checkout',
                'payment_summary' => $paymentSummary,
                'amount_paid' => (float) ($paymentSummary['amount_paid'] ?? 0),
                'payment' => $paymentMeta,
            ], $message, 200);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Verify Razorpay and mark checkout paid (stores amount_paid / payment_status).
     *
     * @param  array<string, mixed>  $data
     * @return array{checkout: CheckoutOrder, payment_summary: array<string, mixed>, payment: array<string, mixed>, message: string}
     */
    protected function finalizeCheckoutOnlinePayment(CheckoutOrder $checkout, array $data): array
    {
        $razorpay = app(\App\Services\Payment\RazorpayService::class);
        if (! $razorpay->enabled()) {
            throw new \RuntimeException('Online payments are not configured.');
        }

        $before = $this->payments->summaryForCheckout($checkout);
        $payableNow = (float) ($before['payable_now'] ?? $before['total_amount'] ?? 0);

        $verified = $razorpay->assertSuccessfulPayment([
            'razorpay_order_id' => $data['razorpay_order_id'] ?? null,
            'razorpay_payment_id' => $data['razorpay_payment_id'] ?? null,
            'razorpay_signature' => $data['razorpay_signature'] ?? null,
        ], $payableNow);

        $checkout = $this->payments->payCheckout($checkout, 'razorpay');
        $after = $this->payments->summaryForCheckout($checkout);

        $message = in_array($after['payment_phase'], ['remaining_due', 'advance_paid_waiting'], true)
            ? 'Advance paid successfully. Remaining amount is due on booking completion. Booking sent to the designer.'
            : 'Payment successful. Booking sent to the designer.';

        return [
            'checkout' => $checkout,
            'payment_summary' => $after,
            'payment' => [
                'method' => 'razorpay',
                'status' => $checkout->payment_status,
                'phase' => $after['payment_phase'],
                'transaction_id' => $verified['payment_id'],
                'razorpay_order_id' => $verified['order_id'],
                'paid_amount' => (float) ($before['payable_now'] ?? 0),
                'amount_paid_total' => (float) ($after['amount_paid'] ?? 0),
                'remaining_amount' => (float) ($after['remaining_amount'] ?? 0),
            ],
            'message' => $message,
        ];
    }

    /**
     * Verify Razorpay and mark a single booking paid.
     *
     * @param  array<string, mixed>  $data
     * @return array{order: Order, payment_summary: array<string, mixed>, payment: array<string, mixed>, message: string}
     */
    protected function finalizeOrderOnlinePayment(Order $order, array $data): array
    {
        $razorpay = app(\App\Services\Payment\RazorpayService::class);
        if (! $razorpay->enabled()) {
            throw new \RuntimeException('Online payments are not configured.');
        }

        $before = $this->payments->summaryForOrder($order);
        $payableNow = (float) ($before['payable_now'] ?? $before['total_amount'] ?? 0);

        $verified = $razorpay->assertSuccessfulPayment([
            'razorpay_order_id' => $data['razorpay_order_id'] ?? null,
            'razorpay_payment_id' => $data['razorpay_payment_id'] ?? null,
            'razorpay_signature' => $data['razorpay_signature'] ?? null,
        ], $payableNow);

        $order = $this->payments->payOrder($order, 'razorpay');
        $after = $this->payments->summaryForOrder($order);

        $message = in_array($after['payment_phase'], ['remaining_due', 'advance_paid_waiting'], true)
            ? 'Advance paid successfully. Remaining amount is due on booking completion. Booking sent to the designer.'
            : 'Payment successful. Booking sent to the designer.';

        return [
            'order' => $order,
            'payment_summary' => $after,
            'payment' => [
                'method' => 'razorpay',
                'status' => $order->payment_status,
                'phase' => $after['payment_phase'],
                'transaction_id' => $verified['payment_id'],
                'razorpay_order_id' => $verified['order_id'],
                'paid_amount' => (float) ($before['payable_now'] ?? 0),
                'amount_paid_total' => (float) ($after['amount_paid'] ?? 0),
                'remaining_amount' => (float) ($after['remaining_amount'] ?? 0),
            ],
            'message' => $message,
        ];
    }

    public function cancel(Request $request, Order $booking): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        abort_unless($booking->customer_id === $customer->id, 403);

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        try {
            $updated = app(\App\Services\Booking\BookingLifecycleService::class)
                ->cancelByCustomer($booking, $data['reason']);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success([
            'booking' => CustomerApiPresenter::bookingDetail($updated->load(['vendor', 'category', 'dispute', 'review'])),
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
            'item_id' => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            $updated = app(\App\Services\Booking\BookingLifecycleService::class)
                ->requestRework($booking, $data['reason'] ?? null, isset($data['item_id']) ? (int) $data['item_id'] : null);
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

        $booking->loadMissing(['orderItems', 'reviews']);

        $requiresItem = $booking->orderItems->isNotEmpty();

        $data = $request->validate([
            'item_id' => [$requiresItem ? 'required' : 'nullable', 'integer', 'min:1'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $item = null;
        $vendorId = $booking->vendor_id;

        if (! empty($data['item_id'])) {
            $item = $booking->orderItems->firstWhere('id', (int) $data['item_id']);
            if (! $item) {
                return $this->error('Item not found on this booking.', 404);
            }

            if (! in_array($item->status, OrderReview::reviewableStatuses(), true)) {
                return $this->error('You can review this item after it is delivered.', 422);
            }

            if ($item->review()->exists() || OrderReview::query()->where('order_item_id', $item->id)->exists()) {
                return $this->error('You have already reviewed this item.', 422);
            }

            $vendorId = $item->vendor_id ?: $booking->vendor_id;
        } else {
            if (! in_array($booking->status, OrderReview::reviewableStatuses(), true)) {
                return $this->error('You can review this booking after it is delivered.', 422);
            }

            if ($booking->reviews()->whereNull('order_item_id')->exists()) {
                return $this->error('You have already reviewed this booking.', 422);
            }
        }

        abort_unless($vendorId, 422, 'Vendor not found for this booking.');

        $review = OrderReview::query()->create([
            'order_id' => $booking->id,
            'order_item_id' => $item?->id,
            'customer_id' => $customer->id,
            'vendor_id' => $vendorId,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        $this->syncVendorRating($vendorId);

        $fresh = $booking->fresh([
            'vendor',
            'category',
            'dispute',
            'review',
            'reviews.orderItem',
            'orderItems.review',
            'orderItems.portfolioItem.category',
            'orderItems.driver',
        ]);

        return $this->success([
            'review' => CustomerApiPresenter::orderReview($review->load(['customer', 'order', 'orderItem'])),
            'booking' => CustomerApiPresenter::bookingDetail($fresh),
        ], $item ? 'Item review submitted.' : 'Review submitted.');
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
