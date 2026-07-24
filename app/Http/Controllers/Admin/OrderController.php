<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Models\CheckoutOrder;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Vendor;
use App\Http\Requests\Admin\OrderRequest;
use App\Support\AdminCityScope;
use App\Support\AppliesListDateFilter;
use App\Support\CodeGenerator;
use App\Services\Vendor\VendorWalletService;
use App\Support\StoresUploadedFiles;
use App\Support\OrderDispatchSupport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends AdminController
{
    use AppliesListDateFilter;

    protected string $permissionModule = 'orders';

    public function index(Request $request): View
    {
        $this->validateListDateRange($request);

        $standaloneQuery = AdminCityScope::scopeOrders(
            $this->applyDateRange(Order::query()->whereNull('checkout_order_id'), $request)
        )
            ->with(['customer', 'vendor', 'category']);

        $checkoutQuery = AdminCityScope::scopeCheckoutOrders(
            $this->applyDateRange(CheckoutOrder::query(), $request)
        )
            ->with(['customer', 'subOrders.vendor'])
            ->withCount('subOrders');

        if ($request->filled('search')) {
            $term = '%'.$request->string('search').'%';
            $standaloneQuery->where('order_number', 'like', $term);
            $checkoutQuery->where('order_number', 'like', $term);
        }

        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            $standaloneQuery->where('status', $status);
            $checkoutQuery->where('status', $status);
        }

        if ($request->filled('payment_status')) {
            $paymentStatus = $request->string('payment_status')->toString();
            $standaloneQuery->where('payment_status', $paymentStatus);
            $checkoutQuery->where('payment_status', $paymentStatus);
        }

        if ($request->filled('vendor_id')) {
            $vendorId = $request->integer('vendor_id');
            $standaloneQuery->where('vendor_id', $vendorId);
            $checkoutQuery->whereHas('subOrders', fn ($q) => $q->where('vendor_id', $vendorId));
        }

        if ($request->filled('category_id')) {
            $categoryId = $request->integer('category_id');
            $standaloneQuery->where('category_id', $categoryId);
            $checkoutQuery->whereHas('subOrders', fn ($q) => $q->where('category_id', $categoryId));
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
        $perPage = 15;
        $orders = new LengthAwarePaginator(
            $entries->slice(($page - 1) * $perPage, $perPage)->values(),
            $entries->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $vendors = AdminCityScope::scopeVendors(Vendor::query())->active()->orderBy('brand_name')->get();
        $categories = Category::query()
            ->where('is_active', true)
            ->where('type', 'service')
            ->orderBy('name')
            ->get();

        return view('admin.orders.index', compact('orders', 'vendors', 'categories'));
    }

    public function create(): View
    {
        return view('admin.orders.create', [
            'customers' => AdminCityScope::scopeCustomers(Customer::query())->orderBy('name')->get(),
            'vendors' => AdminCityScope::scopeVendors(Vendor::query())->active()->orderBy('brand_name')->get(),
            'drivers' => AdminCityScope::scopeDrivers(Driver::query())->where('status', 'active')->orderBy('name')->get(),
            'categories' => Category::query()->where('is_active', true)->where('type', 'service')->orderBy('name')->get(),
        ]);
    }

    public function store(OrderRequest $request): RedirectResponse
    {
        $data = $this->orderPayload($request);

        $order = Order::query()->create([
            ...$data,
            'order_number' => CodeGenerator::orderNumber(),
            'order_type' => $data['order_type'] ?? 'rental',
            'quantity' => $data['quantity'] ?? 1,
        ]);

        $this->applyOrderUploads($request, $order);
        $this->syncCustomerOrderCount($order->customer_id);

        return redirect()->route('admin.orders.show', $order)->with('success', 'Order created successfully.');
    }

    public function show(Order $order): View
    {
        $order->load([
            'customer',
            'vendor',
            'driver',
            'category',
            'refund',
            'dispute',
            'checkoutOrder',
            'orderItems.portfolioItem',
            'orderItems.driver',
        ]);

        return view('admin.orders.show', [
            'order' => $order,
            'drivers' => Driver::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function edit(Order $order): View
    {
        return view('admin.orders.edit', [
            'order' => $order,
            'customers' => AdminCityScope::scopeCustomers(Customer::query())->orderBy('name')->get(),
            'vendors' => AdminCityScope::scopeVendors(Vendor::query())->orderBy('brand_name')->get(),
            'drivers' => AdminCityScope::scopeDrivers(Driver::query())->where('status', 'active')->orderBy('name')->get(),
            'categories' => Category::query()->where('is_active', true)->where('type', 'service')->orderBy('name')->get(),
        ]);
    }

    public function update(OrderRequest $request, Order $order): RedirectResponse
    {
        $data = $this->orderPayload($request);
        $order->update($data);
        $this->applyOrderUploads($request, $order);
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
            'status' => ['required', 'in:'.implode(',', Order::STATUSES)],
        ]);

        $previousPaymentStatus = $order->payment_status;

        try {
            if ($data['status'] !== $order->status) {
                app(\App\Services\Checkout\VendorBookingItemService::class)
                    ->setActiveItemsStatus($order->fresh(['orderItems']), $data['status']);
            }
        } catch (\InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $this->applyStatusSideEffects($order->fresh(), $data['status']);
        $this->syncWalletOnPaymentSuccess($order->fresh(), $previousPaymentStatus);

        return back()->with('success', $this->statusUpdateMessage($order->fresh(), $data['status']));
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

        $previousPaymentStatus = $order->payment_status;
        $statusChanged = $data['status'] !== $order->status;

        // Admin may still override status for ops, but prefer the lifecycle graph.
        if ($statusChanged && ! OrderDispatchSupport::canTransitionTo($order, $data['status'])) {
            return back()->with('error', 'Invalid status transition from '.$order->status.' to '.$data['status'].'.');
        }

        // Driver can only be assigned when booking status is In Transit or Return In Transit.
        // Check the submitted status so admin can set "In Transit" + driver in one save.
        $assigningOrChangingDriver = filled($data['driver_id'])
            && (int) $data['driver_id'] !== (int) ($order->driver_id ?? 0);

        if ($assigningOrChangingDriver && ! OrderDispatchSupport::isDispatchStatus($data['status'])) {
            return back()
                ->withInput()
                ->with('error', 'Assign a driver only after the booking is In Transit (or Return In Transit). First set Order status to In Transit, then assign the driver.');
        }

        if ($statusChanged && $data['status'] === 're_intransit') {
            OrderDispatchSupport::resetDriverAssignment($order);
        } elseif ($statusChanged && $data['status'] === 'rework') {
            OrderDispatchSupport::resetDriverAssignment($order);
        } elseif ($statusChanged && $data['status'] === 'in_progress') {
            OrderDispatchSupport::prepareForTransit($order);
        }

        // When assigning a driver for a dispatch leg, set delivery sub-status.
        if (filled($data['driver_id']) && (int) $data['driver_id'] !== (int) $order->driver_id) {
            $data['driver_delivery_status'] = Order::DRIVER_STATUS_ACCEPTED;
            $data['driver_assigned_at'] = now();
            $data['driver_rejection_reason'] = null;
            if (blank($order->delivery_otp)) {
                $data['delivery_otp'] = Order::generateDeliveryOtpValue();
            }
        }

        $order->fill(collect($data)->except(['status'])->all());
        $order->save();

        if ($statusChanged) {
            app(\App\Services\Checkout\VendorBookingItemService::class)
                ->setActiveItemsStatus($order->fresh(['orderItems']), $data['status']);
        }

        $this->applyStatusSideEffects($order->fresh(), $data['status'], $data['payment_status']);
        $this->syncWalletOnPaymentSuccess($order->fresh(), $previousPaymentStatus);

        return back()->with('success', $this->manageSuccessMessage($order->fresh(), $data));
    }

    /**
     * Assign / clear a driver for a single line item (only when that item is In Transit).
     */
    public function assignItemDriver(Request $request, Order $order, OrderItem $item): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        abort_unless((int) $item->order_id === (int) $order->id, 404);

        $data = $request->validate([
            'driver_id' => ['nullable', 'exists:drivers,id'],
        ]);

        if (! $item->canAssignDriver()) {
            return back()->with(
                'error',
                'Assign a driver for "'.$item->title().'" only when its status is In Transit or Return In Transit.'
            );
        }

        // Vendor marked "returned" early — reopen as Return In Transit so admin can assign pickup driver.
        if ($item->status === 'returned') {
            $item->update(['status' => 're_intransit']);
            $item->refresh();
        }

        $driverId = $data['driver_id'] ?? null;
        $previousItemDriverId = $item->driver_id;

        $item->update([
            'driver_id' => $driverId ?: null,
            'driver_assigned_at' => $driverId ? now() : null,
            'driver_delivery_status' => $driverId ? Order::DRIVER_STATUS_ACCEPTED : null,
            'driver_pickup_at' => null,
        ]);

        // Keep booking-level driver in sync with the latest in-transit item assignment.
        // Admin assignment = already accepted (driver skips accept and can pickup).
        if ($driverId) {
            $order->update([
                'driver_id' => $driverId,
                'driver_delivery_status' => Order::DRIVER_STATUS_ACCEPTED,
                'driver_assigned_at' => $order->driver_assigned_at ?: now(),
                'driver_rejection_reason' => null,
                'delivery_otp' => $order->delivery_otp ?: Order::generateDeliveryOtpValue(),
            ]);
        } elseif ($previousItemDriverId && (int) $order->driver_id === (int) $previousItemDriverId) {
            // Cleared the same driver from this item — drop booking driver if no other items keep them.
            $stillAssigned = $order->orderItems()
                ->where('driver_id', $previousItemDriverId)
                ->where('id', '!=', $item->id)
                ->exists();

            if (! $stillAssigned) {
                $order->update([
                    'driver_id' => null,
                    'driver_delivery_status' => null,
                    'driver_assigned_at' => null,
                    'driver_pickup_at' => null,
                ]);
            }
        }

        $message = $driverId
            ? (
                $item->status === 're_intransit'
                    ? 'Return driver assigned to "'.$item->title().'" (pickup from customer → deliver to vendor).'
                    : 'Driver assigned to item "'.$item->title().'".'
            )
            : 'Driver cleared from item "'.$item->title().'".';

        return back()->with('success', $message);
    }

    protected function syncWalletOnPaymentSuccess(Order $order, string $previousPaymentStatus): void
    {
        if ($previousPaymentStatus !== 'success' && $order->payment_status === 'success') {
            if (! $order->paid_at) {
                $order->update(['paid_at' => now()]);
            }

            app(VendorWalletService::class)->creditFromPayment($order->fresh());
        }
    }

    protected function applyStatusSideEffects(Order $order, string $status, ?string $paymentStatus = null): void
    {
        // Do not auto-mark payment success on delivery when an advance is already paid —
        // remaining balance is collected separately after completion.
        if (
            $status === 'delivered'
            && ($paymentStatus === 'pending' || $order->payment_status === 'pending')
            && (float) ($order->advance_amount ?? 0) <= 0
            && (float) ($order->amount_paid ?? 0) <= 0
        ) {
            $order->update([
                'payment_status' => 'success',
                'paid_at' => $order->paid_at ?? now(),
            ]);
        }

        if ($status === 'refunded') {
            $order->update(['payment_status' => 'refunded']);
        }

        if ($status === 'cancelled' && $order->payment_status === 'pending') {
            $order->update(['payment_status' => 'failed']);
        }
    }

    protected function statusUpdateMessage(Order $order, string $status): string
    {
        $message = 'Order status updated to '.Order::statusLabelFor($status).'.';

        if ($status === 'cancelled' && $order->refund) {
            $message .= ' A refund request has been created for the paid amount.';
        }

        return $message;
    }

    /** @param array{status: string} $data */
    protected function manageSuccessMessage(Order $order, array $data): string
    {
        if ($data['status'] === 'cancelled' && $order->refund) {
            return 'Order updated. A refund request has been created for the paid amount.';
        }

        return 'Order updated successfully.';
    }

    protected function syncCustomerOrderCount(int $customerId): void
    {
        $customer = Customer::query()->find($customerId);
        $customer?->update(['total_orders' => $customer->orders()->count()]);
    }

    /** @return array<string, mixed> */
    protected function orderPayload(OrderRequest $request): array
    {
        return collect($request->validated())
            ->except(['item_image', 'reference_images'])
            ->all();
    }

    protected function applyOrderUploads(OrderRequest $request, Order $order): void
    {
        $updates = [];

        if ($request->hasFile('item_image')) {
            $updates['item_image_path'] = StoresUploadedFiles::replace(
                $request->file('item_image'),
                $order->item_image_path,
                'orders/items'
            );
        }

        if ($request->hasFile('reference_images')) {
            $paths = $order->reference_image_paths ?? [];
            foreach ($request->file('reference_images') as $file) {
                $paths[] = StoresUploadedFiles::store($file, 'orders/references');
            }
            $updates['reference_image_paths'] = $paths;
        }

        if ($updates !== []) {
            $order->update($updates);
        }
    }
}
