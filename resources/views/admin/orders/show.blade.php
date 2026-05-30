@extends('admin.layouts.app')

@section('title', $order->order_number)
@section('page_title', $order->order_number)
@section('page_subtitle', $order->orderTypeLabel().' · '.$order->itemDisplayName())

@section('header_actions')
    @if (auth('admin')->user()->hasPermission('orders', 'edit'))
        <x-admin.button variant="secondary" :href="route('admin.orders.edit', $order)">Edit full order</x-admin.button>
    @endif
@endsection

@section('content')
    {{-- Summary header --}}
    <div class="jb-order-summary">
        <div class="jb-order-summary-main">
            <div class="jb-order-summary-badges">
                <span class="jb-order-type-badge jb-order-type-badge--{{ $order->order_type }}">{{ $order->orderTypeLabel() }}</span>
                @include('admin.components.status-badge', ['status' => $order->status])
                @include('admin.components.status-badge', ['status' => $order->payment_status, 'label' => 'Payment: '.ucfirst($order->payment_status)])
            </div>
            <h2 class="jb-order-summary-title">{{ $order->itemDisplayName() }}</h2>
            <p class="jb-order-summary-meta">
                {{ $order->category->name }}
                @if ($order->size) · Size {{ $order->size }} @endif
                @if ($order->color) · {{ $order->color }} @endif
                · Qty {{ $order->quantity ?? 1 }}
            </p>
            <p class="jb-order-summary-meta">Placed {{ $order->created_at->format('M d, Y h:i A') }} · Updated {{ $order->updated_at->format('M d, Y h:i A') }}</p>
        </div>
        <div class="jb-order-summary-amount">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Grand total</p>
            <p class="text-2xl font-bold text-slate-900">₹{{ number_format($order->grandTotal(), 2) }}</p>
            <p class="mt-1 text-sm text-slate-500">Outfit ₹{{ number_format($order->amount, 2) }}</p>
        </div>
    </div>

    {{-- Workflow timeline --}}
    <div class="jb-card mt-6">
        <div class="jb-card-header">
            <p class="jb-card-header-title">Order workflow</p>
            @if (in_array($order->status, ['cancelled', 'refunded'], true))
                <span class="text-sm font-semibold text-rose-600">{{ ucfirst($order->status) }}</span>
            @endif
        </div>
        <div class="jb-card-body">
            <ol class="jb-order-timeline">
                @foreach ($order->workflowSteps() as $step)
                    <li class="jb-order-timeline-step jb-order-timeline-step--{{ $step['state'] }}">
                        <span class="jb-order-timeline-dot" aria-hidden="true"></span>
                        <span class="jb-order-timeline-label">{{ $step['label'] }}</span>
                    </li>
                @endforeach
            </ol>
        </div>
    </div>

    <div class="jb-detail-grid mt-6">
        {{-- Item & rental --}}
        <div class="jb-detail-card lg:col-span-2">
            <h2>Outfit & booking</h2>
            <dl class="jb-dl jb-dl--grid">
                <div><dt>Item</dt><dd class="font-semibold">{{ $order->item_title ?: '—' }}</dd></div>
                <div><dt>Category</dt><dd>{{ $order->category->name }}</dd></div>
                <div><dt>Type</dt><dd>{{ $order->orderTypeLabel() }}</dd></div>
                <div><dt>Size</dt><dd>{{ $order->size ?? '—' }}</dd></div>
                <div><dt>Color</dt><dd>{{ $order->color ?? '—' }}</dd></div>
                <div><dt>Quantity</dt><dd>{{ $order->quantity ?? 1 }}</dd></div>
                @if ($order->item_description)
                    <div class="sm:col-span-2"><dt>Description</dt><dd>{{ $order->item_description }}</dd></div>
                @endif
                <div><dt>Event date</dt><dd>{{ $order->event_date?->format('M d, Y') ?? '—' }}</dd></div>
                <div><dt>Rental period</dt><dd>
                    @if ($order->rental_start_date || $order->rental_end_date)
                        {{ $order->rental_start_date?->format('M d, Y') ?? '?' }}
                        →
                        {{ $order->rental_end_date?->format('M d, Y') ?? '?' }}
                    @else
                        —
                    @endif
                </dd></div>
                <div><dt>Return due</dt><dd>{{ $order->return_due_date?->format('M d, Y') ?? '—' }}</dd></div>
            </dl>
        </div>

        {{-- Delivery --}}
        <div class="jb-detail-card">
            <h2>Delivery</h2>
            <dl class="jb-dl">
                <div><dt>City</dt><dd>{{ $order->city ?? '—' }}</dd></div>
                <div><dt>Pincode</dt><dd>{{ $order->pincode ?? '—' }}</dd></div>
                <div><dt>Delivery address</dt><dd>{{ $order->delivery_address ?? '—' }}</dd></div>
                <div><dt>Pickup / return</dt><dd>{{ $order->pickup_address ?? '—' }}</dd></div>
            </dl>
        </div>

        {{-- Payment --}}
        <div class="jb-detail-card">
            <h2>Payment breakdown</h2>
            <dl class="jb-dl">
                <div><dt>Outfit amount</dt><dd>₹{{ number_format($order->amount, 2) }}</dd></div>
                <div><dt>Security deposit</dt><dd>₹{{ number_format($order->security_deposit ?? 0, 2) }}</dd></div>
                <div><dt>Delivery fee</dt><dd>₹{{ number_format($order->delivery_fee ?? 0, 2) }}</dd></div>
                <div><dt>Grand total</dt><dd class="text-lg font-bold">₹{{ number_format($order->grandTotal(), 2) }}</dd></div>
                <div><dt>Payment</dt><dd>@include('admin.components.status-badge', ['status' => $order->payment_status, 'label' => ucfirst($order->payment_status)])</dd></div>
            </dl>
        </div>

        {{-- Customer --}}
        <div class="jb-detail-card">
            <h2>Customer</h2>
            <div class="jb-actor-cell mb-4">
                @include('admin.partials.actor-avatar', [
                    'imageUrl' => $order->customer->profileImageUrl(),
                    'label' => $order->customer->name,
                    'size' => 'md',
                ])
                <div>
                    <a href="{{ route('admin.customers.show', $order->customer) }}" class="jb-link font-semibold">{{ $order->customer->name }}</a>
                    <p class="text-xs text-slate-500">{{ $order->customer->customer_code }}</p>
                </div>
            </div>
            <dl class="jb-dl">
                <div><dt>Mobile</dt><dd>{{ $order->customer->mobile }}</dd></div>
                <div><dt>Email</dt><dd>{{ $order->customer->email ?? '—' }}</dd></div>
                <div><dt>City</dt><dd>{{ $order->customer->city ?? '—' }}</dd></div>
            </dl>
        </div>

        {{-- Vendor --}}
        <div class="jb-detail-card">
            <h2>Vendor / boutique</h2>
            @if ($order->vendor)
                <div class="jb-actor-cell mb-4">
                    @include('admin.partials.actor-avatar', [
                        'imageUrl' => $order->vendor->profileImageUrl(),
                        'fallbackUrl' => $order->vendor->shopLogoUrl(),
                        'label' => $order->vendor->brand_name,
                        'size' => 'md',
                    ])
                    <div>
                        <a href="{{ route('admin.vendors.show', $order->vendor) }}" class="jb-link font-semibold">{{ $order->vendor->brand_name }}</a>
                        <p class="text-xs text-slate-500">{{ $order->vendor->vendor_code }}</p>
                    </div>
                </div>
                <dl class="jb-dl">
                    <div><dt>Owner</dt><dd>{{ $order->vendor->owner_name }}</dd></div>
                    <div><dt>Mobile</dt><dd>{{ $order->vendor->mobile }}</dd></div>
                    <div><dt>Service</dt><dd>{{ $order->vendor->serviceType() ?? '—' }}</dd></div>
                </dl>
            @else
                <p class="text-sm text-slate-500">No vendor assigned yet.</p>
            @endif
        </div>

        {{-- Driver --}}
        <div class="jb-detail-card">
            <h2>Delivery driver</h2>
            @if ($order->driver)
                <div class="jb-actor-cell mb-4">
                    @include('admin.partials.actor-avatar', [
                        'imageUrl' => $order->driver->profileImageUrl(),
                        'label' => $order->driver->name,
                        'size' => 'md',
                    ])
                    <div>
                        <a href="{{ route('admin.drivers.show', $order->driver) }}" class="jb-link font-semibold">{{ $order->driver->name }}</a>
                        <p class="text-xs text-slate-500">{{ $order->driver->driver_code }}</p>
                    </div>
                </div>
                <dl class="jb-dl">
                    <div><dt>Mobile</dt><dd>{{ $order->driver->mobile }}</dd></div>
                    <div><dt>Vehicle</dt><dd>{{ $order->driver->vehicle_no ?? '—' }}</dd></div>
                </dl>
            @else
                <p class="text-sm text-slate-500">No driver assigned. Assign below.</p>
            @endif
        </div>

        {{-- Notes --}}
        <div class="jb-detail-card lg:col-span-2">
            <h2>Notes</h2>
            <dl class="jb-dl">
                <div><dt>Customer notes</dt><dd>{{ $order->customer_notes ?: '—' }}</dd></div>
                <div><dt>Admin notes</dt><dd>{{ $order->admin_notes ?: '—' }}</dd></div>
            </dl>
        </div>

        {{-- Related --}}
        @if ($order->refund || $order->dispute)
            <div class="jb-detail-card lg:col-span-2">
                <h2>Related records</h2>
                <dl class="jb-dl">
                    @if ($order->refund)
                        <div><dt>Refund</dt><dd><a href="{{ route('admin.refunds.show', $order->refund) }}" class="jb-link">{{ ucfirst($order->refund->status) }} — ₹{{ number_format($order->refund->amount, 2) }}</a></dd></div>
                    @endif
                    @if ($order->dispute)
                        <div><dt>Dispute</dt><dd><a href="{{ route('admin.disputes.show', $order->dispute) }}" class="jb-link">{{ $order->dispute->subject }}</a></dd></div>
                    @endif
                </dl>
            </div>
        @endif
    </div>

    @if (auth('admin')->user()->hasPermission('orders', 'edit'))
        <div class="jb-card mt-6 max-w-4xl">
            <div class="jb-card-header">
                <p class="jb-card-header-title">Update status & assignment</p>
            </div>
            <div class="jb-card-body">
                <form method="POST" action="{{ route('admin.orders.manage', $order) }}" class="jb-form-grid">
                    @csrf
                    <x-admin.form-select label="Order status" name="status" :required="true">
                        @foreach (\App\Models\Order::STATUSES as $s)
                            <option value="{{ $s }}" @selected($order->status === $s)>{{ str_replace('_', ' ', ucfirst($s)) }}</option>
                        @endforeach
                    </x-admin.form-select>
                    <x-admin.form-select label="Payment status" name="payment_status" :required="true">
                        @foreach (\App\Models\Order::PAYMENT_STATUSES as $s)
                            <option value="{{ $s }}" @selected($order->payment_status === $s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </x-admin.form-select>
                    <x-admin.form-select label="Assign driver" name="driver_id">
                        <option value="">Unassigned</option>
                        @foreach ($drivers as $d)
                            <option value="{{ $d->id }}" @selected($order->driver_id == $d->id)>{{ $d->name }} — {{ $d->mobile }}</option>
                        @endforeach
                    </x-admin.form-select>
                    <div class="sm:col-span-2">
                        <label for="admin_notes_quick" class="jb-label">Admin notes</label>
                        <textarea id="admin_notes_quick" name="admin_notes" rows="3" class="jb-input">{{ old('admin_notes', $order->admin_notes) }}</textarea>
                    </div>
                    <div class="jb-form-actions sm:col-span-2">
                        <x-admin.button variant="primary" type="submit">Save changes</x-admin.button>
                    </div>
                </form>
            </div>
        </div>

        @include('admin.partials.status-actions-panel', [
            'title' => 'Quick workflow actions',
            'actions' => $order->quickStatusActions(),
        ])
    @endif
@endsection
