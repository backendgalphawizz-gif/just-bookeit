<?php

namespace App\Http\Controllers\Web;

use App\Models\CheckoutOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReview;
use App\Models\PortfolioItem;
use App\Models\Vendor;
use App\Services\Booking\BookingLifecycleService;
use App\Services\Booking\BookingPaymentService;
use App\Services\Booking\BookingPricingService;
use App\Services\Web\WebBookingService;
use App\Support\Api\CustomerBookingTab;
use App\Support\BookingMeasurementSupport;
use App\Support\WebMeasurementForm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class BookingController extends WebController
{
    public function __construct(
        protected WebBookingService $bookings,
        protected BookingPaymentService $payments,
        protected BookingLifecycleService $lifecycle
    ) {}

    public function index(Request $request): View
    {
        $customer = Auth::guard('customer')->user();
        $tab = $request->input('tab');
        $categorySlug = CustomerBookingTab::categorySlug($tab);

        $standaloneQuery = Order::query()
            ->with(['vendor', 'category', 'orderItems', 'portfolioItem'])
            ->where('customer_id', $customer->id)
            ->whereNull('checkout_order_id');

        if ($categorySlug) {
            $standaloneQuery = CustomerBookingTab::applyToQuery($standaloneQuery, $tab);
        }

        $checkoutQuery = CheckoutOrder::query()
            ->with([
                'subOrders.vendor',
                'subOrders.category',
                'subOrders.orderItems',
                'subOrders.portfolioItem',
            ])
            ->withCount('subOrders')
            ->where('customer_id', $customer->id);

        if ($categorySlug) {
            $checkoutQuery->whereHas('subOrders.category', fn ($q) => $q->where('slug', $categorySlug));
        }

        $entries = $standaloneQuery->get()->map(fn (Order $order) => [
            'kind' => 'standalone',
            'sort_at' => $order->created_at,
            'order' => $order,
            'checkout' => null,
        ])->concat(
            $checkoutQuery->get()->map(fn (CheckoutOrder $checkout) => [
                'kind' => 'checkout',
                'sort_at' => $checkout->created_at,
                'order' => null,
                'checkout' => $checkout,
            ])
        )->sortByDesc(fn (array $row) => $row['sort_at']?->timestamp ?? 0)->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 10;
        $orders = new LengthAwarePaginator(
            $entries->slice(($page - 1) * $perPage, $perPage)->values(),
            $entries->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('web.bookings.index', compact('orders'));
    }

    public function show(Order $order): View|RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($order->customer_id === $customer->id, 403);

        if ($order->checkout_order_id) {
            return redirect()->route('web.bookings.checkout.show', $order->checkout_order_id);
        }

        $order->load([
            'customer',
            'vendor',
            'driver',
            'category',
            'dispute',
            'reviews',
            'orderItems.portfolioItem',
            'orderItems.driver',
            'orderItems.review',
            'portfolioItem',
        ]);
        $paymentSummary = $this->payments->summaryForOrder($order);

        return view('web.bookings.show', compact('order', 'paymentSummary'));
    }

    public function showCheckout(CheckoutOrder $checkoutOrder): View|RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($checkoutOrder->customer_id === $customer->id, 403);

        $checkoutOrder->load([
            'subOrders.vendor',
            'subOrders.category',
            'subOrders.driver',
            'subOrders.reviews',
            'subOrders.orderItems.portfolioItem',
            'subOrders.orderItems.driver',
            'subOrders.orderItems.review',
            'subOrders.portfolioItem',
            'refunds',
        ]);
        $paymentSummary = $this->payments->summaryForCheckout($checkoutOrder);

        return view('web.bookings.checkout', compact('checkoutOrder', 'paymentSummary'));
    }

    public function overview(PortfolioItem $item): View|RedirectResponse
    {
        abort_unless($item->isCatalogAvailable(), 404);

        $customer = Auth::guard('customer')->user();

        $item->load(['vendor', 'category', 'subcategory.parent', 'variants']);

        $selectedVariantId = request()->integer('variant') ?: (int) old('portfolio_item_variant_id');
        $selectedVariant = $selectedVariantId ? $item->findVariant($selectedVariantId) : null;

        $addresses = $customer->addresses()->orderByDesc('is_default')->orderByDesc('id')->get();
        $defaultAddress = $customer->defaultAddress();
        $measurementProfiles = $customer->measurements()->latest('id')->get();
        $selectedProfileId = (int) old('measurement_profile_id', request()->query('measurement_profile_id', 0));
        $measurement = $measurementProfiles->firstWhere('id', $selectedProfileId)
            ?? $measurementProfiles->first();

        $rentalDays = BookingPricingService::rentalDays(
            old('rental_start_date'),
            old('rental_end_date'),
        );

        $pricing = BookingPricingService::forPortfolioItem($item, [
            'rental_days' => $rentalDays,
            'requires_rental_period' => $item->requiresRentalPeriod(),
            'daily_rate' => $item->dailyRateFor($selectedVariant),
        ]);

        $measurementValues = WebMeasurementForm::valuesFromProfile($measurement);
        $measurementSections = WebMeasurementForm::sections();

        return view('web.bookings.overview', compact('item', 'addresses', 'defaultAddress', 'measurement', 'measurementProfiles', 'pricing', 'rentalDays', 'measurementValues', 'measurementSections', 'selectedVariant', 'selectedVariantId'));
    }

    public function preview(Request $request, PortfolioItem $item): \Illuminate\Http\JsonResponse
    {
        abort_unless($item->isCatalogAvailable(), 404);

        $data = $request->validate([
            'rental_start_date' => ['nullable', 'date'],
            'rental_end_date' => ['nullable', 'date', 'after_or_equal:rental_start_date'],
            'shipment_required' => ['nullable', 'boolean'],
            'portfolio_item_variant_id' => ['nullable', 'integer', 'exists:portfolio_item_variants,id'],
        ]);

        $variant = null;
        if (! empty($data['portfolio_item_variant_id'])) {
            $variant = $item->findVariant((int) $data['portfolio_item_variant_id']);
        }

        $rentalDays = BookingPricingService::rentalDays(
            $data['rental_start_date'] ?? null,
            $data['rental_end_date'] ?? null,
        );

        $pricing = BookingPricingService::forPortfolioItem($item, [
            'rental_days' => $rentalDays,
            'requires_rental_period' => $item->requiresRentalPeriod(),
            'shipment_required' => filter_var($data['shipment_required'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'daily_rate' => $item->dailyRateFor($variant),
        ]);

        return response()->json(['pricing' => $pricing]);
    }

    public function store(Request $request, PortfolioItem $item): RedirectResponse
    {
        abort_unless($item->isCatalogAvailable(), 404);

        $customer = Auth::guard('customer')->user();
        $this->bookings->assertCanBook($customer);

        $item->loadMissing('variants');

        $requiresRentalPeriod = $item->requiresRentalPeriod();

        $data = $request->validate(array_merge([
            'delivery_address' => ['required', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'pincode' => \App\Support\AdminValidationRules::pincodeRules(),
            'rental_start_date' => [$requiresRentalPeriod ? 'required' : 'nullable', 'date', 'after_or_equal:today'],
            'rental_end_date' => [$requiresRentalPeriod ? 'required' : 'nullable', 'date', 'after_or_equal:rental_start_date'],
            'event_date' => ['nullable', 'date'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
            'reference_images' => ['nullable', 'array', 'max:5'],
            'reference_images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'size' => ['nullable', 'string', 'max:50'],
            'portfolio_item_variant_id' => ['nullable', 'integer', 'exists:portfolio_item_variants,id'],
            'address_id' => ['nullable', 'integer', 'exists:customer_addresses,id'],
            'measurement_profile_id' => ['nullable', 'integer'],
            'measurement_id' => ['nullable', 'integer'],
        ], BookingMeasurementSupport::checkoutValidationRules()));

        if (! $requiresRentalPeriod && (empty($data['rental_start_date']) || empty($data['rental_end_date']))) {
            $data['rental_start_date'] = null;
            $data['rental_end_date'] = null;
        }

        if ($request->filled('portfolio_item_variant_id')) {
            $variant = $item->findVariant((int) $data['portfolio_item_variant_id']);
            if (! $variant) {
                throw ValidationException::withMessages([
                    'portfolio_item_variant_id' => 'Please select a valid size or color.',
                ]);
            }
        }

        if ($request->filled('address_id')) {
            $address = $customer->addresses()->find($request->integer('address_id'));
            abort_unless($address, 403);

            $data['delivery_address'] = $address->fullAddress();
            $data['city'] = $address->city;
            $data['pincode'] = $address->pincode;
        }

        $measurement = BookingMeasurementSupport::resolveProfile($customer, $data);
        if ($request->filled('measurement_profile_id') || $request->filled('measurement_id')) {
            abort_unless($measurement, 422, 'The selected measurement profile was not found.');
        }
        if ($measurement) {
            $data['_measurement_profile'] = $measurement;
        }

        $result = $this->bookings->createFromRequest($customer, $item, $data, $request);

        return redirect()
            ->route('web.bookings.payment', $result['order'])
            ->with('success', 'Booking created. Complete payment to send your request to the designer.');
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($order->customer_id === $customer->id, 403);

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        try {
            $updated = $this->lifecycle->cancelByCustomer($order, $data['reason']);
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return $this->redirectAfterLifecycle($updated)->with('success', 'Booking cancelled.');
    }

    public function confirmReceived(Order $order): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($order->customer_id === $customer->id, 403);

        try {
            $updated = $this->lifecycle->confirmReceived($order);
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $message = $updated->status === 'rental_active'
            ? 'Order received. Rental is now active.'
            : 'Order received.';

        return $this->redirectAfterLifecycle($updated)->with('success', $message);
    }

    public function requestReturn(Request $request, Order $order): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($order->customer_id === $customer->id, 403);

        $data = $request->validate([
            'item_id' => ['nullable', 'integer', 'exists:order_items,id'],
        ]);

        $item = null;
        if (! empty($data['item_id'])) {
            $item = OrderItem::query()->findOrFail($data['item_id']);
        }

        try {
            $updated = $this->lifecycle->requestReturn($order, $item);
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return $this->redirectAfterLifecycle($updated)
            ->with('success', 'Return pickup requested for rented product(s).');
    }

    public function requestRework(Request $request, Order $order): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($order->customer_id === $customer->id, 403);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'min:5', 'max:1000'],
            'item_id' => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            $updated = $this->lifecycle->requestRework(
                $order,
                $data['reason'] ?? null,
                isset($data['item_id']) ? (int) $data['item_id'] : null
            );
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return $this->redirectAfterLifecycle($updated)->with('success', 'Rework requested.');
    }

    public function review(Request $request, Order $order): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($order->customer_id === $customer->id, 403);

        $order->loadMissing(['orderItems', 'reviews']);
        $requiresItem = $order->orderItems->isNotEmpty();

        $data = $request->validate([
            'item_id' => [$requiresItem ? 'required' : 'nullable', 'integer', 'min:1'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $item = null;
        $vendorId = $order->vendor_id;

        if (! empty($data['item_id'])) {
            $item = $order->orderItems->firstWhere('id', (int) $data['item_id']);
            if (! $item) {
                return back()->with('error', 'Item not found on this booking.');
            }

            if (! in_array($item->status, OrderReview::reviewableStatuses(), true)) {
                return back()->with('error', 'You can review this item after it is delivered.');
            }

            if ($item->review()->exists()) {
                return back()->with('error', 'You have already reviewed this item.');
            }

            $vendorId = $item->vendor_id ?: $order->vendor_id;
        } else {
            if (! in_array($order->status, OrderReview::reviewableStatuses(), true)) {
                return back()->with('error', 'You can review this booking after it is delivered.');
            }

            if ($order->reviews()->whereNull('order_item_id')->exists()) {
                return back()->with('error', 'You have already reviewed this booking.');
            }
        }

        if (! $vendorId) {
            return back()->with('error', 'Vendor not found for this booking.');
        }

        OrderReview::query()->create([
            'order_id' => $order->id,
            'order_item_id' => $item?->id,
            'customer_id' => $customer->id,
            'vendor_id' => $vendorId,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        $average = OrderReview::query()->where('vendor_id', $vendorId)->avg('rating');
        Vendor::query()->whereKey($vendorId)->update([
            'rating' => round((float) $average, 2),
        ]);

        return $this->redirectAfterLifecycle($order)
            ->with('success', $item ? 'Item review submitted.' : 'Review submitted.');
    }

    protected function redirectAfterLifecycle(Order $order): RedirectResponse
    {
        if ($order->checkout_order_id) {
            return redirect()->route('web.bookings.checkout.show', $order->checkout_order_id);
        }

        return redirect()->route('web.bookings.show', $order);
    }
}
