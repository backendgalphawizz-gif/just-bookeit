<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Vendor;
use App\Http\Requests\Admin\OrderRequest;
use App\Support\AppliesListDateFilter;
use App\Support\CodeGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends AdminController
{
    use AppliesListDateFilter;

    protected string $permissionModule = 'orders';

    public function index(Request $request): View
    {
        $this->validateListDateRange($request);

        $orders = $this->applyDateRange(Order::query(), $request)
            ->with(['customer', 'vendor', 'category'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where('order_number', 'like', $term);
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('payment_status'), fn ($q) => $q->where('payment_status', $request->string('payment_status')))
            ->when($request->filled('vendor_id'), fn ($q) => $q->where('vendor_id', $request->integer('vendor_id')))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $vendors = Vendor::query()->active()->orderBy('brand_name')->get();

        return view('admin.orders.index', compact('orders', 'vendors'));
    }

    public function create(): View
    {
        return view('admin.orders.create', [
            'customers' => Customer::query()->orderBy('name')->get(),
            'vendors' => Vendor::query()->active()->orderBy('brand_name')->get(),
            'drivers' => Driver::query()->where('status', 'active')->orderBy('name')->get(),
            'categories' => Category::query()->where('type', 'service')->orderBy('name')->get(),
        ]);
    }

    public function store(OrderRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $order = Order::query()->create([
            ...$data,
            'order_number' => CodeGenerator::orderNumber(),
            'order_type' => $data['order_type'] ?? 'rental',
            'quantity' => $data['quantity'] ?? 1,
        ]);

        $this->syncCustomerOrderCount($order->customer_id);

        return redirect()->route('admin.orders.show', $order)->with('success', 'Order created successfully.');
    }

    public function show(Order $order): View
    {
        $order->load(['customer', 'vendor', 'driver', 'category', 'refund', 'dispute']);

        return view('admin.orders.show', [
            'order' => $order,
            'drivers' => Driver::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function edit(Order $order): View
    {
        return view('admin.orders.edit', [
            'order' => $order,
            'customers' => Customer::query()->orderBy('name')->get(),
            'vendors' => Vendor::query()->orderBy('brand_name')->get(),
            'drivers' => Driver::query()->where('status', 'active')->orderBy('name')->get(),
            'categories' => Category::query()->where('type', 'service')->orderBy('name')->get(),
        ]);
    }

    public function update(OrderRequest $request, Order $order): RedirectResponse
    {
        $data = $request->validated();
        $order->update($data);
        $this->syncCustomerOrderCount($order->customer_id);

        return redirect()->route('admin.orders.show', $order)->with('success', 'Order updated successfully.');
    }

    public function destroy(Order $order): RedirectResponse
    {
        $customerId = $order->customer_id;
        $order->delete();
        $this->syncCustomerOrderCount($customerId);

        return redirect()->route('admin.orders.index')->with('success', 'Order deleted successfully.');
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $data = $request->validate([
            'status' => ['required', 'in:new,pending_acceptance,accepted,in_progress,in_transit,delivered,cancelled,refunded'],
        ]);

        $order->update(['status' => $data['status']]);
        $this->applyStatusSideEffects($order, $data['status']);

        return back()->with('success', 'Order status updated to '.str_replace('_', ' ', $data['status']).'.');
    }

    public function manage(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $data = $request->validate([
            'status' => ['required', Rule::in(Order::STATUSES)],
            'payment_status' => ['required', Rule::in(Order::PAYMENT_STATUSES)],
            'driver_id' => ['nullable', 'exists:drivers,id'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $order->update($data);
        $this->applyStatusSideEffects($order, $data['status'], $data['payment_status']);

        return back()->with('success', 'Order updated successfully.');
    }

    protected function applyStatusSideEffects(Order $order, string $status, ?string $paymentStatus = null): void
    {
        if ($status === 'delivered' && ($paymentStatus === 'pending' || $order->payment_status === 'pending')) {
            $order->update(['payment_status' => 'success']);
        }

        if ($status === 'refunded') {
            $order->update(['payment_status' => 'refunded']);
        }

        if ($status === 'cancelled' && $order->payment_status === 'pending') {
            $order->update(['payment_status' => 'failed']);
        }
    }

    protected function syncCustomerOrderCount(int $customerId): void
    {
        $customer = Customer::query()->find($customerId);
        $customer?->update(['total_orders' => $customer->orders()->count()]);
    }
}
