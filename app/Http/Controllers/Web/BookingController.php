<?php

namespace App\Http\Controllers\Web;

use App\Models\Order;
use App\Models\PortfolioItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BookingController extends WebController
{
    public function index(Request $request): View
    {
        $customer = Auth::guard('customer')->user();

        $orders = Order::query()
            ->with(['vendor', 'category'])
            ->where('customer_id', $customer->id)
            ->when($request->filled('tab'), function ($q) use ($request) {
                if ($request->string('tab') === 'rental') {
                    $q->where('order_type', 'rental');
                }
            })
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

    public function overview(PortfolioItem $item): View
    {
        $item->load(['vendor', 'category']);

        return view('web.bookings.overview', compact('item'));
    }
}
