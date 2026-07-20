@extends('admin.layouts.app')

@section('title', 'Booking '.$order->order_number)
@section('page_title', 'Booking Detail')
@section('page_subtitle', 'Booked '.$order->created_at->format('M d, Y · H:i'))
@section('back_href', route('admin.orders.index'))

@section('header_actions')
    @if (auth('admin')->user()->hasPermission('orders', 'edit'))
        <x-admin.button variant="secondary" :href="route('admin.orders.edit', $order)">Edit booking</x-admin.button>
    @endif
@endsection

@section('content')
    <div class="jb-booking-header">
        <div>
            <p class="jb-booking-id">#{{ $order->order_number }}</p>
            @if ($order->checkoutOrder)
                <p style="margin:0.35rem 0 0;font-size:0.8125rem">
                    Part of checkout
                    <a href="{{ route('admin.checkout-orders.show', $order->checkoutOrder) }}" style="font-weight:700;color:var(--jb-primary,#4f46e5)">
                        #{{ $order->checkoutOrder->order_number }}
                    </a>
                </p>
            @endif
            <div class="jb-booking-header-badges">
                <span class="jb-order-type-badge jb-order-type-badge--{{ $order->order_type }}">{{ $order->orderTypeLabel() }}</span>
                @include('admin.components.status-badge', ['status' => $order->status])
            </div>
        </div>
        <p class="jb-booking-booked-on">Booked on {{ $order->created_at->format('M d, Y · H:i') }}</p>
    </div>

    <div class="jb-booking-layout">
        {{-- LEFT COLUMN --}}
        <div class="jb-booking-main">
            {{-- Product --}}
            <div class="jb-booking-card jb-booking-product">
                <h3 class="jb-booking-card-title">Product detail</h3>
                @if ($order->orderItems->isNotEmpty())
                    <div class="jb-booking-line-items">
                        @foreach ($order->orderItems as $lineItem)
                            @php
                                $lineImage = $lineItem->displayImageUrl()
                                    ?: $lineItem->portfolioItem?->displayImageUrl();
                                $variantLabel = $lineItem->variantLabel();
                            @endphp
                            <div class="jb-booking-product-row" @style(['margin-top: 0.85rem' => ! $loop->first, 'padding-top: 0.85rem' => ! $loop->first, 'border-top: 1px solid var(--jb-border, #e2e8f0)' => ! $loop->first])>
                                <div class="jb-booking-product-media">
                                    @if ($lineImage)
                                        <img src="{{ $lineImage }}" alt="" class="jb-booking-product-img panel-lightbox-trigger">
                                    @else
                                        <div class="jb-booking-product-placeholder">
                                            <svg class="size-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="jb-booking-product-info">
                                    <p class="jb-booking-product-name">{{ $lineItem->title() }}</p>
                                    <p class="jb-booking-product-meta">
                                        @if ($lineItem->categoryName()){{ $lineItem->categoryName() }}@endif
                                        @if ($variantLabel){{ $lineItem->categoryName() ? ' · ' : '' }}{{ $variantLabel }}@endif
                                        @if ($lineItem->status){{ ($lineItem->categoryName() || $variantLabel) ? ' · ' : '' }}{{ $lineItem->statusLabel() }}@endif
                                    </p>
                                    <p class="jb-booking-product-price">₹{{ number_format((float) $lineItem->line_amount, 0) }}</p>
                                    <p class="jb-booking-product-qty">Qty — {{ (int) $lineItem->quantity }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if ($order->isRental() && ($order->rental_start_date || $order->rental_end_date))
                        <p class="jb-booking-product-meta" style="margin-top:0.85rem">
                            Rental period:
                            {{ $order->rental_start_date?->format('M d') ?? '—' }}
                            – {{ $order->rental_end_date?->format('M d, Y') ?? '—' }}
                            @if ($order->rentalDurationDays()) ({{ $order->rentalDurationDays() }} days) @endif
                        </p>
                    @endif
                    @if ($order->orderItems->count() > 1)
                        <p class="jb-booking-product-meta" style="margin-top:0.5rem">
                            {{ $order->orderItems->count() }} items · total qty {{ (int) ($order->quantity ?? $order->orderItems->sum('quantity')) }}
                        </p>
                    @endif
                @else
                    <div class="jb-booking-product-row">
                        <div class="jb-booking-product-media">
                            @if ($order->itemImageUrl())
                                <img src="{{ $order->itemImageUrl() }}" alt="" class="jb-booking-product-img panel-lightbox-trigger">
                            @else
                                <div class="jb-booking-product-placeholder">
                                    <svg class="size-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div class="jb-booking-product-info">
                            <p class="jb-booking-product-name">{{ $order->itemDisplayName() }}</p>
                            <p class="jb-booking-product-meta">
                                @if ($order->color){{ $order->color }}@endif
                                @if ($order->size) | Size: {{ $order->size }}@endif
                            </p>
                            @if ($order->isRental() && ($order->rental_start_date || $order->rental_end_date))
                                <p class="jb-booking-product-meta">
                                    Rental period:
                                    {{ $order->rental_start_date?->format('M d') ?? '—' }}
                                    – {{ $order->rental_end_date?->format('M d, Y') ?? '—' }}
                                    @if ($order->rentalDurationDays()) ({{ $order->rentalDurationDays() }} days) @endif
                                </p>
                            @endif
                            <p class="jb-booking-product-price">₹{{ number_format($order->amount, 0) }}</p>
                            <p class="jb-booking-product-qty">Qty — {{ $order->quantity ?? 1 }}</p>
                        </div>
                    </div>
                @endif
            </div>

            @if ($order->isRental())
                @include('admin.orders.partials.rent-tracking')
            @endif

            {{-- Designer + Rental period --}}
            <div @class(['jb-booking-split', 'jb-booking-split--single' => ! $order->isRental()])>
                <div class="jb-booking-card jb-booking-card--compact">
                    <h3 class="jb-booking-card-title">Designer</h3>
                    @if ($order->vendor)
                        <div class="jb-booking-designer">
                            @include('admin.partials.actor-avatar', [
                                'imageUrl' => $order->vendor->profileImageUrl(),
                                'fallbackUrl' => $order->vendor->shopLogoUrl(),
                                'label' => $order->vendor->brand_name,
                            ])
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('admin.vendors.show', $order->vendor) }}" class="jb-booking-designer-name" title="{{ $order->vendor->brand_name }}">{{ $order->vendor->brand_name }}</a>
                                <p class="jb-booking-designer-meta">★ {{ number_format($order->vendor->rating, 1) }} · {{ $order->vendor->city ?? '—' }}</p>
                            </div>
                            <a href="tel:{{ $order->vendor->mobile }}" class="jb-booking-call-btn" title="Call vendor">📞</a>
                        </div>
                    @else
                        <p class="text-sm text-slate-500">No designer assigned</p>
                    @endif
                </div>
                @if ($order->requiresRentalPeriod() || $order->rental_start_date || $order->rental_end_date)
                    <div class="jb-booking-card jb-booking-card--compact jb-schedule-card">
                        <h3 class="jb-booking-card-title">Rental period</h3>
                        <div class="jb-schedule-grid">
                            <div class="jb-schedule-item">
                                <span class="jb-schedule-label">Start date</span>
                                <strong class="jb-schedule-value">{{ $order->rental_start_date?->format('d M Y') ?? 'Not set' }}</strong>
                            </div>
                            <div class="jb-schedule-item">
                                <span class="jb-schedule-label">End date</span>
                                <strong class="jb-schedule-value">{{ $order->rental_end_date?->format('d M Y') ?? 'Not set' }}</strong>
                            </div>
                            <div class="jb-schedule-item">
                                <span class="jb-schedule-label">Rental duration</span>
                                <strong class="jb-schedule-value">
                                    @if ($order->rentalDurationDays())
                                        {{ $order->rentalDurationDays() }} {{ \Illuminate\Support\Str::plural('day', $order->rentalDurationDays()) }}
                                    @else
                                        Not provided
                                    @endif
                                </strong>
                            </div>
                        </div>
                    </div>
                @elseif ($order->event_date)
                    <div class="jb-booking-card jb-booking-card--compact">
                        <h3 class="jb-booking-card-title">Event date</h3>
                        <p class="jb-booking-rental-dates">{{ $order->event_date->format('d M, Y') }}</p>
                    </div>
                @endif
            </div>

            {{-- Shipping --}}
            <div class="jb-booking-card">
                <h3 class="jb-booking-card-title">
                    <span class="jb-booking-icon-pin" aria-hidden="true">📍</span> Shipping address
                </h3>
                <p class="jb-booking-address-name" title="{{ $order->customer->name }}">{{ $order->customer->name }}</p>
                <p class="jb-booking-address-text">{{ $order->delivery_address ?? '—' }}</p>
                @if ($order->city || $order->pincode)
                    <p class="jb-booking-address-text">{{ $order->city }}@if($order->pincode), {{ $order->pincode }}@endif</p>
                @endif
            </div>

            {{-- Measurements --}}
            @php $orderMeasurements = \App\Support\BookingMeasurementSupport::orderMeasurements($order); @endphp
            @include('admin.orders.partials.measurements', [
                'measurements' => $orderMeasurements,
                'title' => $order->customer->name."'s measurements",
            ])
            <div class="jb-booking-card jb-booking-card--compact">
                <a href="{{ route('admin.customers.show', $order->customer) }}" class="jb-booking-link">View full customer profile</a>
            </div>

            {{-- Notes --}}
            @if ($order->customer_notes)
                <div class="jb-booking-card">
                    <h3 class="jb-booking-card-title">Custom notes</h3>
                    <p class="jb-booking-notes">{{ $order->customer_notes }}</p>
                </div>
            @endif

            {{-- Reference images --}}
            @if (count($order->referenceImageUrls()) > 0)
                <div class="jb-booking-card">
                    <h3 class="jb-booking-card-title">Reference images</h3>
                    <div class="jb-booking-ref-grid">
                        @foreach ($order->referenceImageUrls() as $url)
                            <div class="jb-booking-ref-thumb">
                                <img src="{{ $url }}" alt="Reference image" class="panel-lightbox-trigger">
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Driver --}}
            @if ($order->driver)
                <div class="jb-booking-card">
                    <h3 class="jb-booking-card-title">Delivery driver</h3>
                    <div class="jb-booking-designer">
                        @include('admin.partials.actor-avatar', [
                            'imageUrl' => $order->driver->profileImageUrl(),
                            'label' => $order->driver->name,
                        ])
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('admin.drivers.show', $order->driver) }}" class="jb-booking-designer-name" title="{{ $order->driver->name }}">{{ $order->driver->name }}</a>
                            <p class="jb-booking-designer-meta">{{ $order->driver->vehicle_no ?? 'No vehicle' }} · {{ $order->driver->mobile }}</p>
                        </div>
                        <a href="tel:{{ $order->driver->mobile }}" class="jb-booking-call-btn" title="Call driver">📞</a>
                    </div>
                </div>
            @endif
        </div>

        {{-- RIGHT SIDEBAR --}}
        <div class="jb-booking-sidebar">
            {{-- Track booking (delivery) --}}
            <div class="jb-booking-card">
                <h3 class="jb-booking-card-title">{{ $order->isRental() ? 'Delivery tracking' : 'Track booking' }}</h3>
                <ol class="jb-booking-track">
                    @foreach ($order->trackBookingSteps() as $step)
                        <li class="jb-booking-track-step jb-booking-track-step--{{ $step['state'] }}">
                            <span class="jb-booking-track-marker" aria-hidden="true">
                                @if ($step['state'] === 'done')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                @endif
                            </span>
                            <div class="jb-booking-track-body">
                                <p class="jb-booking-track-label">{{ $step['label'] }}</p>
                                @if ($step['time'])
                                    <p class="jb-booking-track-time">{{ $step['time'] }}</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>

            {{-- Billing --}}
            <div class="jb-booking-card">
                <h3 class="jb-booking-card-title">Billing address</h3>
                <p class="jb-booking-address-name" title="{{ $order->customer->name }}">{{ $order->customer->name }}</p>
                <p class="jb-booking-address-text">{{ $order->billing_address ?? $order->delivery_address ?? '—' }}</p>
            </div>

            @if ($order->admin_notes)
                <div class="jb-booking-card">
                    <h3 class="jb-booking-card-title">Admin comment</h3>
                    <p class="jb-booking-notes break-words">{{ $order->admin_notes }}</p>
                </div>
            @endif

            @if ($order->damage_note || $order->damage_deduct_percent)
                <div class="jb-booking-card">
                    <h3 class="jb-booking-card-title">Damage</h3>
                    <p class="jb-booking-notes">{{ $order->damage_note ?? '—' }}@if($order->damage_deduct_percent) — {{ $order->damage_deduct_percent }}%@endif</p>
                </div>
            @endif

            {{-- Payment summary --}}
            @php $orderPayment = app(\App\Services\Booking\BookingPaymentService::class)->summaryForOrder($order); @endphp
            <div class="jb-booking-card jb-booking-payment">
                <h3 class="jb-booking-card-title">Payment summary</h3>
                <dl class="jb-booking-payment-lines">
                    <div><dt>Subtotal</dt><dd>₹{{ number_format($order->subtotal(), 0) }}</dd></div>
                    @if ($order->damageDeduction() > 0)
                        <div class="jb-booking-payment-damage"><dt>Damage deduction</dt><dd>- ₹{{ number_format($order->damageDeduction(), 0) }}</dd></div>
                    @endif
                    <div><dt>Shipping & handling</dt><dd>₹{{ number_format($order->delivery_fee ?? 0, 0) }}</dd></div>
                    <div><dt>Tax (GST)</dt><dd>₹{{ number_format($order->tax_amount ?? 0, 0) }}</dd></div>
                    @if ($order->security_deposit)
                        <div><dt>Security deposit</dt><dd>₹{{ number_format($order->security_deposit, 0) }}</dd></div>
                    @endif
                    @if (($orderPayment['advance_amount'] ?? 0) > 0)
                        <div><dt>Advance required</dt><dd>₹{{ number_format($orderPayment['advance_amount'], 0) }}</dd></div>
                    @endif
                    @if (($orderPayment['amount_paid'] ?? 0) > 0)
                        <div><dt>Amount paid</dt><dd>₹{{ number_format($orderPayment['amount_paid'], 0) }}</dd></div>
                    @endif
                    @if (($orderPayment['remaining_amount'] ?? 0) > 0)
                        <div><dt>Remaining</dt><dd>₹{{ number_format($orderPayment['remaining_amount'], 0) }}</dd></div>
                    @endif
                    @if (($orderPayment['payable_now'] ?? 0) > 0)
                        <div><dt>Payable now</dt><dd>₹{{ number_format($orderPayment['payable_now'], 0) }}</dd></div>
                    @endif
                    @if ($order->isRental() && $order->rentalDurationDays())
                        <div><dt>Rental duration</dt><dd>{{ $order->rentalDurationDays() }} days</dd></div>
                    @endif
                    @if ($order->event_date)
                        <div><dt>Event date</dt><dd>{{ $order->event_date->format('M d, Y') }}</dd></div>
                    @endif
            </dl>
                <div class="jb-booking-payment-total">
                    <span>Total amount</span>
                    <strong>₹{{ number_format($order->grandTotal(), 0) }}</strong>
                </div>
                <div class="mt-3">
                    @include('admin.components.status-badge', ['status' => $order->payment_status, 'label' => ucfirst(str_replace('_', ' ', $order->payment_status))])
                </div>
                @if ($order->payment_method)
                    <p class="jb-booking-product-meta" style="margin-top:0.5rem">Method: {{ strtoupper(str_replace('_', ' ', $order->payment_method)) }}</p>
                @endif
                @if ($order->paid_at)
                    <p class="jb-booking-product-meta">Paid: {{ $order->paid_at->format('M d, Y · H:i') }}</p>
                @endif
            </div>

            @if ($order->refund || $order->dispute)
                <div class="jb-booking-card">
                    <h3 class="jb-booking-card-title">Related</h3>
                    @if ($order->refund)
                        <p class="text-sm"><a href="{{ route('admin.refunds.show', $order->refund) }}" class="jb-link">Refund — {{ ucfirst($order->refund->status) }}</a></p>
                    @endif
                    @if ($order->dispute)
                        <p class="text-sm mt-2 break-words"><a href="{{ route('admin.disputes.show', $order->dispute) }}" class="jb-link">{{ $order->dispute->subject }}</a></p>
                    @endif
                </div>
            @endif

            {{-- Manage status --}}
            @if (auth('admin')->user()->hasPermission('orders', 'edit'))
                <div class="jb-booking-card jb-booking-manage" style="margin-bottom: 15px;">
                    <h3 class="jb-booking-card-title">Update status</h3>
                    <form method="POST" action="{{ route('admin.orders.manage', $order) }}" class="jb-booking-manage-form">
                        @csrf
                        <div>
                            <label class="jb-label" for="status">Order status</label>
                            <select id="status" name="status" class="jb-select">
                                @foreach (\App\Models\Order::STATUSES as $s)
                                    <option value="{{ $s }}" @selected($order->status === $s)>{{ \App\Models\Order::statusLabelFor($s) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="jb-label" for="payment_status">Payment</label>
                            <select id="payment_status" name="payment_status" class="jb-select">
                                @foreach (\App\Models\Order::PAYMENT_STATUSES as $s)
                                    <option value="{{ $s }}" @selected($order->payment_status === $s)>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="jb-label" for="driver_id">Assign driver</label>
                            <select id="driver_id" name="driver_id" class="jb-select">
                                <option value="">Unassigned</option>
                                @foreach ($drivers as $d)
                                    <option value="{{ $d->id }}" @selected($order->driver_id == $d->id)>{{ $d->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="jb-label" for="admin_notes">Admin notes</label>
                            <textarea id="admin_notes" name="admin_notes" rows="2" class="jb-input">{{ old('admin_notes', $order->admin_notes) }}</textarea>
                        </div>
                        <x-admin.button variant="primary" type="submit" class="w-full">Save changes</x-admin.button>
                    </form>
                </div>

                @php $quickActions = $order->quickStatusActions(); @endphp
                @if (count($quickActions) > 0)
                    <div class="jb-booking-card" style="margin-bottom: 15px;">
                        <h3 class="jb-booking-card-title">Quick actions</h3>
                        <div class="jb-booking-quick-actions">
                            @foreach ($quickActions as $action)
                                <form method="POST" action="{{ $action['url'] }}" class="inline-flex w-full"
                                    @if (! empty($action['confirm']))
                                        data-jb-confirm="{{ $action['confirm'] }}"
                                        data-jb-confirm-title="Confirm action"
                                        data-jb-confirm-label="{{ $action['label'] }}"
                                    @endif
                                >
                                    @csrf
                                    <input type="hidden" name="status" value="{{ $action['status'] }}">
                                    <x-admin.button :variant="$action['variant'] ?? 'secondary'" type="submit" size="sm" class="w-full justify-center">{{ $action['label'] }}</x-admin.button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection
