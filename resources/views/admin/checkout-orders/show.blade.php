@extends('admin.layouts.app')

@section('title', 'Checkout '.$checkout->order_number)
@section('page_title', 'Checkout order')
@section('page_subtitle', $checkout->subOrders->count().' sub-order'.($checkout->subOrders->count() === 1 ? '' : 's').' · '.$checkout->created_at->format('M d, Y · H:i'))
@section('back_href', route('admin.orders.index'))

@php
    $paymentSummary = app(\App\Services\Booking\BookingPaymentService::class)->summaryForCheckout($checkout);
    $measurements = \App\Support\BookingMeasurementSupport::checkoutMeasurements($checkout);

    $rentalStarts = $checkout->subOrders
        ->flatMap(function ($sub) {
            $dates = collect([$sub->rental_start_date?->format('Y-m-d')]);
            foreach ($sub->orderItems as $item) {
                $dates->push($item->rentalStartDate());
            }

            return $dates;
        })
        ->filter()
        ->values();
    $rentalEnds = $checkout->subOrders
        ->flatMap(function ($sub) {
            $dates = collect([$sub->rental_end_date?->format('Y-m-d')]);
            foreach ($sub->orderItems as $item) {
                $dates->push($item->rentalEndDate());
            }

            return $dates;
        })
        ->filter()
        ->values();

    $scheduleStart = $checkout->rental_start_date?->format('Y-m-d') ?: $rentalStarts->min();
    $scheduleEnd = $checkout->rental_end_date?->format('Y-m-d') ?: $rentalEnds->max();
    $rentalDays = ($scheduleStart && $scheduleEnd)
        ? \App\Services\Booking\BookingPricingService::rentalDays($scheduleStart, $scheduleEnd)
        : null;
    $hasRentalService = $checkout->subOrders->contains(fn ($sub) => $sub->requiresRentalPeriod());
    $eventDates = $checkout->subOrders->filter(fn ($s) => $s->event_date);
@endphp

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
                <h3 class="jb-booking-card-title">Customer</h3>
                @if ($checkout->customer)
                    <p class="jb-booking-address-name">
                        <a href="{{ route('admin.customers.show', $checkout->customer) }}">{{ $checkout->customer->name }}</a>
                    </p>
                    <p class="jb-booking-product-meta">
                        {{ $checkout->customer->mobile ?? '—' }}
                        @if ($checkout->customer->email) · {{ $checkout->customer->email }}@endif
                    </p>
                @else
                    <p class="jb-booking-product-meta">Customer unavailable</p>
                @endif
            </div>

            <div class="jb-booking-card">
                <h3 class="jb-booking-card-title">Delivery address</h3>
                <p class="jb-booking-address-text">{{ $checkout->delivery_address ?: '—' }}</p>
                @if ($checkout->city || $checkout->pincode)
                    <p class="jb-booking-address-text">{{ $checkout->city }}@if($checkout->pincode), {{ $checkout->pincode }}@endif</p>
                @endif
                @if ($checkout->billing_address && $checkout->billing_address !== $checkout->delivery_address)
                    <p class="jb-booking-product-meta" style="margin-top:0.75rem"><strong>Billing:</strong> {{ $checkout->billing_address }}</p>
                @endif
            </div>

            @if ($hasRentalService || $scheduleStart || $scheduleEnd || $eventDates->isNotEmpty())
                <div class="jb-booking-card jb-schedule-card">
                    <h3 class="jb-booking-card-title">Rental / schedule</h3>
                    <div class="jb-schedule-grid">
                        <div class="jb-schedule-item">
                            <span class="jb-schedule-label">Start date</span>
                            <strong class="jb-schedule-value">{{ $scheduleStart ? \Illuminate\Support\Carbon::parse($scheduleStart)->format('d M Y') : 'Not set' }}</strong>
                        </div>
                        <div class="jb-schedule-item">
                            <span class="jb-schedule-label">End date</span>
                            <strong class="jb-schedule-value">{{ $scheduleEnd ? \Illuminate\Support\Carbon::parse($scheduleEnd)->format('d M Y') : 'Not set' }}</strong>
                        </div>
                        <div class="jb-schedule-item">
                            <span class="jb-schedule-label">Rental duration</span>
                            <strong class="jb-schedule-value">
                                @if ($rentalDays)
                                    {{ $rentalDays }} {{ \Illuminate\Support\Str::plural('day', $rentalDays) }}
                                @elseif ($hasRentalService)
                                    Not provided
                                @else
                                    —
                                @endif
                            </strong>
                        </div>
                    </div>
                    @foreach ($eventDates as $subWithEvent)
                        <p class="jb-booking-product-meta" style="margin-top:0.65rem">
                            Event ({{ $subWithEvent->sub_order_number ?? $subWithEvent->order_number }}):
                            {{ $subWithEvent->event_date->format('d M Y') }}
                        </p>
                    @endforeach
                </div>
            @endif

            @include('admin.orders.partials.measurements', [
                'measurements' => $measurements,
                'title' => 'Customer measurements',
            ])

            @if ($checkout->customer_notes)
                <div class="jb-booking-card">
                    <h3 class="jb-booking-card-title">Customer notes</h3>
                    <p class="jb-booking-notes">{{ $checkout->customer_notes }}</p>
                </div>
            @endif

            @foreach ($checkout->subOrders as $subOrder)
                <div class="jb-booking-card">
                    <div class="jb-booking-card-head" style="margin-bottom:0.85rem">
                        <div>
                            <h3 class="jb-booking-card-title mb-0">
                                Sub-order {{ $subOrder->sub_order_number ?? $subOrder->order_number }}
                            </h3>
                            <p class="jb-booking-product-meta" style="margin-top:0.35rem">
                                @if ($subOrder->vendor)
                                    <a href="{{ route('admin.vendors.show', $subOrder->vendor) }}">{{ $subOrder->vendor->brand_name }}</a>
                                @else
                                    No vendor
                                @endif
                                @if ($subOrder->category) · {{ $subOrder->category->name }}@endif
                                · {{ $subOrder->orderTypeLabel() }}
                            </p>
                        </div>
                        <div class="jb-booking-header-badges">
                            @include('admin.components.status-badge', ['status' => $subOrder->payment_status, 'label' => ucfirst(str_replace('_', ' ', $subOrder->payment_status))])
                            @include('admin.components.status-badge', ['status' => $subOrder->status])
                            <x-admin.action-btn variant="view" :href="route('admin.orders.show', $subOrder)" />
                        </div>
                    </div>

                    @if ($subOrder->requiresRentalPeriod() || $subOrder->rental_start_date || $subOrder->rental_end_date || $subOrder->event_date)
                        <p class="jb-booking-product-meta" style="margin-bottom:0.75rem">
                            @if ($subOrder->requiresRentalPeriod() || $subOrder->rental_start_date || $subOrder->rental_end_date)
                                Rental:
                                {{ $subOrder->rental_start_date?->format('d M Y') ?? 'Not set' }}
                                –
                                {{ $subOrder->rental_end_date?->format('d M Y') ?? 'Not set' }}
                                · Duration:
                                @if ($subOrder->rentalDurationDays())
                                    {{ $subOrder->rentalDurationDays() }} {{ \Illuminate\Support\Str::plural('day', $subOrder->rentalDurationDays()) }}
                                @else
                                    Not provided
                                @endif
                            @endif
                            @if ($subOrder->event_date)
                                @if ($subOrder->requiresRentalPeriod() || $subOrder->rental_start_date || $subOrder->rental_end_date) · @endif
                                Event: {{ $subOrder->event_date->format('d M Y') }}
                            @endif
                        </p>
                    @endif

                    @if ($subOrder->orderItems->isNotEmpty())
                        @foreach ($subOrder->orderItems as $lineItem)
                            @php
                                $lineImage = $lineItem->displayImageUrl() ?: $lineItem->portfolioItem?->displayImageUrl();
                                $variantLabel = $lineItem->variantLabel();
                                $lineRefs = $lineItem->referenceImageUrls();
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
                                        · Qty {{ (int) $lineItem->quantity }}
                                        · {{ $lineItem->statusLabel() }}
                                    </p>
                                    @if ($lineItem->rentalStartDate() || $lineItem->rentalEndDate())
                                        <p class="jb-booking-product-meta">
                                            Dates: {{ $lineItem->rentalStartDate() ?? '—' }} – {{ $lineItem->rentalEndDate() ?? '—' }}
                                            @if ($lineItem->rentalDurationDays()) ({{ $lineItem->rentalDurationDays() }} days)@endif
                                        </p>
                                    @endif
                                    @if ($lineItem->customerNotes())
                                        <p class="jb-booking-product-meta">Note: {{ $lineItem->customerNotes() }}</p>
                                    @endif
                                    <p class="jb-booking-product-price">
                                        ₹{{ number_format((float) $lineItem->line_amount, 0) }}
                                        @if ($lineItem->advanceAmount() > 0)
                                            <span class="jb-booking-product-meta"> · Advance ₹{{ number_format($lineItem->advanceAmount(), 0) }}</span>
                                        @endif
                                    </p>
                                    @if ($lineRefs !== [])
                                        <div class="jb-booking-ref-grid" style="margin-top:0.5rem">
                                            @foreach ($lineRefs as $url)
                                                <div class="jb-booking-ref-thumb">
                                                    <img src="{{ $url }}" alt="Reference" class="panel-lightbox-trigger">
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="jb-booking-product-row">
                            <div class="jb-booking-product-media">
                                @if ($subOrder->itemImageUrl())
                                    <img src="{{ $subOrder->itemImageUrl() }}" alt="" class="jb-booking-product-img panel-lightbox-trigger">
                                @else
                                    <div class="jb-booking-product-placeholder">
                                        <svg class="size-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="jb-booking-product-info">
                                <p class="jb-booking-product-name">{{ $subOrder->itemDisplayName() }}</p>
                                <p class="jb-booking-product-meta">
                                    Qty {{ (int) ($subOrder->quantity ?? 1) }}
                                    @if ($subOrder->size) · Size {{ $subOrder->size }}@endif
                                    @if ($subOrder->color) · {{ $subOrder->color }}@endif
                                </p>
                                <p class="jb-booking-product-price">₹{{ number_format((float) $subOrder->amount, 0) }}</p>
                            </div>
                        </div>
                    @endif

                    <div class="jb-booking-billing" style="margin-top:1rem;padding-top:0.85rem;border-top:1px solid var(--jb-border,#e2e8f0)">
                        <div class="jb-booking-billing-row"><span class="jb-booking-billing-label">Subtotal</span><span class="jb-booking-billing-value">₹{{ number_format((float) $subOrder->amount, 2) }}</span></div>
                        <div class="jb-booking-billing-row"><span class="jb-booking-billing-label">Delivery</span><span class="jb-booking-billing-value">₹{{ number_format((float) $subOrder->delivery_fee, 2) }}</span></div>
                        <div class="jb-booking-billing-row"><span class="jb-booking-billing-label">Tax</span><span class="jb-booking-billing-value">₹{{ number_format((float) $subOrder->tax_amount, 2) }}</span></div>
                        @if ((float) ($subOrder->advance_amount ?? 0) > 0)
                            <div class="jb-booking-billing-row"><span class="jb-booking-billing-label">Advance</span><span class="jb-booking-billing-value">₹{{ number_format((float) $subOrder->advance_amount, 2) }}</span></div>
                        @endif
                        @if ((float) ($subOrder->amount_paid ?? 0) > 0)
                            <div class="jb-booking-billing-row"><span class="jb-booking-billing-label">Paid</span><span class="jb-booking-billing-value">₹{{ number_format((float) $subOrder->amount_paid, 2) }}</span></div>
                        @endif
                        <div class="jb-booking-billing-row jb-booking-billing-row--total">
                            <span class="jb-booking-billing-label">Total</span>
                            <strong class="jb-booking-billing-value">₹{{ number_format($subOrder->grandTotal(), 2) }}</strong>
                        </div>
                    </div>

                    @if ($subOrder->driver)
                        <p class="jb-booking-product-meta" style="margin-top:0.75rem">
                            Driver:
                            <a href="{{ route('admin.drivers.show', $subOrder->driver) }}">{{ $subOrder->driver->name }}</a>
                            · {{ $subOrder->driver->mobile }}
                        </p>
                    @endif
                </div>
            @endforeach

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
                                <p class="jb-booking-product-meta">
                                    Sub-order:
                                    <a href="{{ route('admin.orders.show', $refund->order) }}">{{ $refund->order->sub_order_number ?? $refund->order->order_number }}</a>
                                </p>
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

        <div class="jb-booking-sidebar">
            <div class="jb-booking-card jb-booking-card--accent">
                <h3 class="jb-booking-card-title">Payment summary</h3>
                <div class="jb-booking-billing">
                    <div class="jb-booking-billing-row">
                        <span class="jb-booking-billing-label">Subtotal</span>
                        <span class="jb-booking-billing-value">₹{{ number_format((float) $checkout->amount, 2) }}</span>
                    </div>
                    <div class="jb-booking-billing-row">
                        <span class="jb-booking-billing-label">Delivery</span>
                        <span class="jb-booking-billing-value">₹{{ number_format((float) $checkout->delivery_fee, 2) }}</span>
                    </div>
                    <div class="jb-booking-billing-row">
                        <span class="jb-booking-billing-label">Tax (GST)</span>
                        <span class="jb-booking-billing-value">₹{{ number_format((float) $checkout->tax_amount, 2) }}</span>
                    </div>
                    @if (($paymentSummary['advance_amount'] ?? 0) > 0)
                        <div class="jb-booking-billing-row">
                            <span class="jb-booking-billing-label">Advance required</span>
                            <span class="jb-booking-billing-value">₹{{ number_format($paymentSummary['advance_amount'], 2) }}</span>
                        </div>
                    @endif
                    @if (($paymentSummary['amount_paid'] ?? 0) > 0)
                        <div class="jb-booking-billing-row">
                            <span class="jb-booking-billing-label">Amount paid</span>
                            <span class="jb-booking-billing-value">₹{{ number_format($paymentSummary['amount_paid'], 2) }}</span>
                        </div>
                    @endif
                    @if (($paymentSummary['remaining_amount'] ?? 0) > 0)
                        <div class="jb-booking-billing-row">
                            <span class="jb-booking-billing-label">Remaining</span>
                            <span class="jb-booking-billing-value">₹{{ number_format($paymentSummary['remaining_amount'], 2) }}</span>
                        </div>
                    @endif
                    <div class="jb-booking-billing-row jb-booking-billing-row--total">
                        <span class="jb-booking-billing-label">Grand total</span>
                        <strong class="jb-booking-billing-value">₹{{ number_format((float) $checkout->grand_total, 2) }}</strong>
                    </div>
                    @if (($paymentSummary['payable_now'] ?? 0) > 0)
                        <div class="jb-booking-billing-row jb-booking-billing-row--accent">
                            <span class="jb-booking-billing-label">Payable now</span>
                            <span class="jb-booking-billing-value">₹{{ number_format($paymentSummary['payable_now'], 2) }}</span>
                        </div>
                    @endif
                    @if ((float) $checkout->amount_refunded > 0)
                        <div class="jb-booking-billing-row" style="color:#b45309">
                            <span class="jb-booking-billing-label">Refunded</span>
                            <span class="jb-booking-billing-value">₹{{ number_format((float) $checkout->amount_refunded, 2) }}</span>
                        </div>
                    @endif
                </div>
                <div class="jb-booking-billing-meta">
                    <p>Phase: <strong>{{ str_replace('_', ' ', $paymentSummary['payment_phase'] ?? '—') }}</strong></p>
                    @if ($checkout->payment_method)
                        <p>Method: <strong>{{ strtoupper(str_replace('_', ' ', $checkout->payment_method)) }}</strong></p>
                    @endif
                    @if ($checkout->paid_at)
                        <p>Last paid: <strong>{{ $checkout->paid_at->format('M d, Y · H:i') }}</strong></p>
                    @endif
                </div>
            </div>

            <div class="jb-booking-card">
                <h3 class="jb-booking-card-title">Order overview</h3>
                <div class="jb-booking-billing">
                    <div class="jb-booking-billing-row">
                        <span class="jb-booking-billing-label">Vendors</span>
                        <span class="jb-booking-billing-value">{{ $checkout->subOrders->count() }}</span>
                    </div>
                    <div class="jb-booking-billing-row">
                        <span class="jb-booking-billing-label">Line items</span>
                        <span class="jb-booking-billing-value">{{ $checkout->subOrders->sum(fn ($s) => max(1, $s->orderItems->count())) }}</span>
                    </div>
                    <div class="jb-booking-billing-row">
                        <span class="jb-booking-billing-label">Created</span>
                        <span class="jb-booking-billing-value">{{ $checkout->created_at->format('M d, Y') }}</span>
                    </div>
                    @if ($hasRentalService)
                        <div class="jb-booking-billing-row">
                            <span class="jb-booking-billing-label">Rental duration</span>
                            <span class="jb-booking-billing-value">
                                @if ($rentalDays)
                                    {{ $rentalDays }} {{ \Illuminate\Support\Str::plural('day', $rentalDays) }}
                                @else
                                    Not provided
                                @endif
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
