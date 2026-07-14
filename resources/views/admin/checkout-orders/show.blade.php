@extends('admin.layouts.app')

@section('title', 'Checkout '.$checkout->order_number)
@section('page_title', 'Checkout order')
@section('page_subtitle', $checkout->subOrders->count().' sub-order'.($checkout->subOrders->count() === 1 ? '' : 's').' · '.$checkout->created_at->format('M d, Y · H:i'))
@section('back_href', route('admin.checkout-orders.index'))

@section('content')
    <div class="jb-booking-header">
        <div>
            <p class="jb-booking-id">#{{ $checkout->order_number }}</p>
            <div class="jb-booking-header-badges">
                @include('admin.components.status-badge', ['status' => $checkout->payment_status, 'label' => ucfirst(str_replace('_', ' ', $checkout->payment_status))])
                @include('admin.components.status-badge', ['status' => $checkout->status, 'label' => $checkout->statusLabel()])
            </div>
        </div>
        <p class="jb-booking-booked-on">Placed on {{ $checkout->created_at->format('M d, Y · H:i') }}</p>
    </div>

    <div class="jb-booking-layout">
        <div class="jb-booking-main">
            <div class="jb-booking-card">
                <h3 class="jb-booking-card-title">Customer &amp; delivery</h3>
                <p class="jb-booking-product-meta">
                    <a href="{{ route('admin.customers.show', $checkout->customer) }}">{{ $checkout->customer->name }}</a>
                    · {{ $checkout->customer->mobile }}
                </p>
                <p class="jb-booking-product-meta">{{ $checkout->delivery_address }}</p>
                @if ($checkout->city || $checkout->pincode)
                    <p class="jb-booking-product-meta">{{ $checkout->city }}@if($checkout->pincode) · {{ $checkout->pincode }}@endif</p>
                @endif
                @if ($checkout->rental_start_date)
                    <p class="jb-booking-product-meta">
                        Rental: {{ $checkout->rental_start_date->format('M d') }} – {{ $checkout->rental_end_date?->format('M d, Y') }}
                    </p>
                @endif
                @if ($checkout->customer_notes)
                    <p class="jb-booking-product-meta" style="margin-top:0.75rem"><strong>Notes:</strong> {{ $checkout->customer_notes }}</p>
                @endif
            </div>

            @php $measurements = \App\Support\BookingMeasurementSupport::checkoutMeasurements($checkout); @endphp
            @if ($measurements['measurement_type'] || $measurements['height_cm'] || $measurements['chest_cm'] || $measurements['waist_cm'])
                <div class="jb-booking-card jb-booking-card--compact">
                    <h3 class="jb-booking-card-title">Measurements (once per checkout)</h3>
                    <div class="jb-booking-product-meta">
                        @if ($measurements['measurement_type']) Type: {{ ucfirst($measurements['measurement_type']) }}<br>@endif
                        @if ($measurements['height_cm']) Height: {{ $measurements['height_cm'] }} cm<br>@endif
                        @if ($measurements['chest_cm']) Chest: {{ $measurements['chest_cm'] }} cm<br>@endif
                        @if ($measurements['waist_cm']) Waist: {{ $measurements['waist_cm'] }} cm @endif
                    </div>
                </div>
            @endif

            <div class="jb-booking-card">
                <h3 class="jb-booking-card-title">Sub-orders (per vendor)</h3>
                <div class="jb-table-wrap">
                    <table class="jb-table">
                        <thead>
                            <tr>
                                <th>Sub-order</th>
                                <th>Vendor</th>
                                <th>Items</th>
                                <th>Amount</th>
                                <th>Delivery</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($checkout->subOrders as $subOrder)
                                <tr>
                                    <td>{{ $subOrder->sub_order_number ?? $subOrder->order_number }}</td>
                                    <td>
                                        @if ($subOrder->vendor)
                                            <a href="{{ route('admin.vendors.show', $subOrder->vendor) }}">{{ $subOrder->vendor->brand_name }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if ($subOrder->orderItems->isNotEmpty())
                                            <ul style="margin:0;padding-left:1rem;list-style:disc">
                                                @foreach ($subOrder->orderItems as $lineItem)
                                                    <li style="margin:0.15rem 0">
                                                        {{ $lineItem->title() }}
                                                        <span style="color:var(--jb-muted,#64748b)">
                                                            · qty {{ (int) $lineItem->quantity }}
                                                            @if ($lineItem->variantLabel()) · {{ $lineItem->variantLabel() }}@endif
                                                            · ₹{{ number_format((float) $lineItem->line_amount, 0) }}
                                                        </span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            {{ $subOrder->itemDisplayName() }}
                                            <span style="color:var(--jb-muted,#64748b)"> · qty {{ (int) ($subOrder->quantity ?? 1) }}</span>
                                        @endif
                                    </td>
                                    <td>₹{{ number_format($subOrder->amount, 2) }}</td>
                                    <td>₹{{ number_format($subOrder->delivery_fee, 2) }}</td>
                                    <td>@include('admin.components.status-badge', ['status' => $subOrder->payment_status, 'label' => ucfirst($subOrder->payment_status)])</td>
                                    <td>@include('admin.components.status-badge', ['status' => $subOrder->status])</td>
                                    <td>
                                        <x-admin.action-btn variant="view" :href="route('admin.orders.show', $subOrder)" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($checkout->refunds->isNotEmpty())
                <div class="jb-booking-card">
                    <h3 class="jb-booking-card-title">Refunds</h3>
                    @foreach ($checkout->refunds as $refund)
                        <div style="padding:0.75rem 0;border-bottom:1px solid var(--jb-border,#e2e8f0)">
                            <p style="margin:0 0 0.35rem;font-weight:600">
                                ₹{{ number_format($refund->amount, 2) }}
                                · {{ ucfirst($refund->status) }}
                                @if ($refund->auto_processed) <span class="jb-order-type-badge jb-order-type-badge--rental">Auto</span> @endif
                            </p>
                            <p class="jb-booking-product-meta">{{ $refund->reason }}</p>
                            @if ($refund->order)
                                <p class="jb-booking-product-meta">Sub-order: <a href="{{ route('admin.orders.show', $refund->order) }}">{{ $refund->order->sub_order_number ?? $refund->order->order_number }}</a></p>
                            @endif
                            @if ($refund->histories->isNotEmpty())
                                <ul style="margin:0.5rem 0 0;padding-left:1.1rem;font-size:0.8125rem;color:var(--jb-muted,#64748b)">
                                    @foreach ($refund->histories as $history)
                                        <li>{{ $history->created_at->format('M d, H:i') }} — {{ $history->status }}: {{ $history->note }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="jb-booking-side">
            <div class="jb-booking-card jb-booking-card--accent">
                <h3 class="jb-booking-card-title">Payment summary</h3>
                <div class="jb-booking-billing-row"><span>Subtotal</span><span>₹{{ number_format($checkout->amount, 2) }}</span></div>
                <div class="jb-booking-billing-row"><span>Delivery (all vendors)</span><span>₹{{ number_format($checkout->delivery_fee, 2) }}</span></div>
                <div class="jb-booking-billing-row"><span>Tax</span><span>₹{{ number_format($checkout->tax_amount, 2) }}</span></div>
                <div class="jb-booking-billing-row jb-booking-billing-row--total"><span>Grand total</span><strong>₹{{ number_format($checkout->grand_total, 2) }}</strong></div>
                @if ((float) $checkout->amount_refunded > 0)
                    <div class="jb-booking-billing-row" style="color:#b45309"><span>Refunded</span><span>₹{{ number_format($checkout->amount_refunded, 2) }}</span></div>
                @endif
                @if ($checkout->payment_method)
                    <p class="jb-booking-product-meta" style="margin-top:0.75rem">Method: {{ strtoupper(str_replace('_', ' ', $checkout->payment_method)) }}</p>
                @endif
                @if ($checkout->paid_at)
                    <p class="jb-booking-product-meta">Paid: {{ $checkout->paid_at->format('M d, Y · H:i') }}</p>
                @endif
            </div>
        </div>
    </div>
@endsection
