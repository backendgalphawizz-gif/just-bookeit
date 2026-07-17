<?php

namespace App\Http\Controllers\Web;

use App\Models\CheckoutOrder;
use App\Models\Order;
use App\Models\PortfolioItem;
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

class BookingController extends WebController
{
    public function __construct(
        protected WebBookingService $bookings
    ) {}

    public function index(Request $request): View
    {
        $customer = Auth::guard('customer')->user();
        $tab = $request->input('tab');
        $categorySlug = CustomerBookingTab::categorySlug($tab);

        $standaloneQuery = Order::query()
            ->with(['vendor', 'category'])
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

        $order->load(['customer', 'vendor', 'driver', 'category', 'dispute']);

        return view('web.bookings.show', compact('order'));
    }

    public function showCheckout(CheckoutOrder $checkoutOrder): View|RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($checkoutOrder->customer_id === $customer->id, 403);

        $checkoutOrder->load([
            'subOrders.vendor',
            'subOrders.category',
            'subOrders.driver',
            'subOrders.orderItems',
            'refunds',
        ]);

        return view('web.bookings.checkout', compact('checkoutOrder'));
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

        $data = $request->validate(array_merge([
            'delivery_address' => ['required', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:10'],
            'rental_start_date' => ['required', 'date', 'after_or_equal:today'],
            'rental_end_date' => ['required', 'date', 'after_or_equal:rental_start_date'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
            'size' => ['nullable', 'string', 'max:10'],
            'portfolio_item_variant_id' => ['nullable', 'integer', 'exists:portfolio_item_variants,id'],
            'address_id' => ['nullable', 'integer', 'exists:customer_addresses,id'],
            'measurement_profile_id' => ['nullable', 'integer'],
        ], BookingMeasurementSupport::validationRules()));

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

        $measurement = null;
        if ($request->filled('measurement_profile_id')) {
            $measurement = $customer->measurements()->whereKey($request->integer('measurement_profile_id'))->first();
        }
        $measurement ??= $customer->measurements()->latest('id')->first();
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

        if (! in_array($order->status, ['new', 'pending_acceptance'], true)) {
            return back()->with('error', 'This booking can no longer be cancelled.');
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $order->update([
            'status' => 'cancelled',
            'cancellation_reason' => $data['reason'],
        ]);

        return redirect()
            ->route('web.bookings.show', $order)
            ->with('success', 'Booking cancelled.');
    }
}
