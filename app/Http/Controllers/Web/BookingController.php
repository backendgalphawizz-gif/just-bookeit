<?php

namespace App\Http\Controllers\Web;

use App\Models\Order;
use App\Models\PortfolioItem;
use App\Services\Booking\BookingPricingService;
use App\Services\Web\WebBookingService;
use App\Support\Api\CustomerBookingTab;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BookingController extends WebController
{
    public function __construct(
        protected WebBookingService $bookings
    ) {}

    public function index(Request $request): View
    {
        $customer = Auth::guard('customer')->user();

        $orders = CustomerBookingTab::applyToQuery(
            Order::query()
                ->with(['vendor', 'category'])
                ->where('customer_id', $customer->id),
            $request->input('tab')
        )
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('web.bookings.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($order->customer_id === $customer->id, 403);

        $order->load(['customer', 'vendor', 'driver', 'category', 'dispute']);

        return view('web.bookings.show', compact('order'));
    }

    public function overview(PortfolioItem $item): View|RedirectResponse
    {
        abort_unless($item->isCatalogAvailable(), 404);

        $customer = Auth::guard('customer')->user();

        $item->load(['vendor', 'category', 'subcategory.parent']);

        $addresses = $customer->addresses()->orderByDesc('is_default')->orderByDesc('id')->get();
        $defaultAddress = $customer->defaultAddress();
        $measurement = $customer->measurements()->latest('id')->first();

        $rentalDays = BookingPricingService::rentalDays(
            old('rental_start_date'),
            old('rental_end_date'),
        );

        $pricing = BookingPricingService::forPortfolioItem($item, [
            'rental_days' => $rentalDays,
        ]);

        return view('web.bookings.overview', compact('item', 'addresses', 'defaultAddress', 'measurement', 'pricing', 'rentalDays'));
    }

    public function store(Request $request, PortfolioItem $item): RedirectResponse
    {
        abort_unless($item->isCatalogAvailable(), 404);

        $customer = Auth::guard('customer')->user();
        $this->bookings->assertCanBook($customer);

        $data = $request->validate([
            'delivery_address' => ['required', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:10'],
            'rental_start_date' => ['required', 'date', 'after_or_equal:today'],
            'rental_end_date' => ['required', 'date', 'after_or_equal:rental_start_date'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
            'size' => ['nullable', 'string', 'max:10'],
            'address_id' => ['nullable', 'integer', 'exists:customer_addresses,id'],
        ]);

        if ($request->filled('address_id')) {
            $address = $customer->addresses()->find($request->integer('address_id'));
            abort_unless($address, 403);

            $data['delivery_address'] = $address->fullAddress();
            $data['city'] = $address->city;
            $data['pincode'] = $address->pincode;
        }

        $measurement = $customer->measurements()->latest('id')->first();
        if ($measurement) {
            $data['measure_height_cm'] = $measurement->height_cm;
            $data['measure_chest_cm'] = $measurement->chest_cm;
            $data['measure_waist_cm'] = $measurement->waist_cm;
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
