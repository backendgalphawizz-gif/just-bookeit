@extends('vendor.layouts.app')

@section('title', 'Booking Details - '.$booking->order_number)

@section('content')
<div class="vp-details-page-wrapper">

    <div class="vp-details-header">
        <div class="vp-header-left">
            <h1 class="vp-details-main-title">Booking Details</h1>
            <p class="vp-details-meta">
                <span>BID-{{ $booking->order_number ?? $booking->id }}</span> &nbsp;•&nbsp;
                <span>{{ $booking->created_at instanceof \Carbon\Carbon ? $booking->created_at->format('M d, Y - h:i A') : 'Oct 25, 2023 - 10:30 AM' }}</span>
            </p>
        </div>

        <div class="vp-header-right">
            @if (in_array($booking->status, ['new','pending_acceptance']))
                <div class="vp-header-actions-row">
                    <form method="POST" action="{{ route('vendor.bookings.accept', $booking) }}">
                        @csrf
                        <button type="submit" class="vp-details-btn-accept">Accept</button>
                    </form>
                    <form method="POST" action="{{ route('vendor.bookings.reject', $booking) }}"
                          data-vp-confirm="This booking will be rejected."
                          data-vp-confirm-title="Reject booking?"
                          data-vp-confirm-label="Reject"
                          data-vp-confirm-variant="error">
                        @csrf
                        <button type="submit" class="vp-details-btn-reject">Reject</button>
                    </form>
                </div>
            @endif

            <a href="{{ route('vendor.bookings.index') }}" class="vp-details-btn-close" title="Close details">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </a>
        </div>
    </div>

    <div class="vp-details-main-layout">

        <div class="vp-layout-col-left">



            <div class="vp-details-section">
                <h3 class="vp-section-card-title">Product Detail</h3>
                <div class="vp-product-detail-inline-card">
                    <div class="vp-prod-card-left">
                        <img src="http://127.0.0.1:8000/storage/vendors/profile-images/aDGLqDE2ZvU8M5kASSxNHO7lW13tSZERgstsg25T.png" alt="Product Image" class="vp-prod-detail-img" onerror="this.onerror=null; this.src='https://placehold.co/80x80?text=Dress';">
                        <div class="vp-prod-info-block">
                            <h4 class="vp-prod-info-title">{{ method_exists($booking, 'itemDisplayName') ? $booking->itemDisplayName() : 'Velvet Gown' }}</h4>
                            <span class="vp-prod-category-tag">{{ $booking->service_type ?? 'Rental Dresses' }}</span>
                            <p class="vp-prod-rental-dates">Rental: {{ $booking->rental_duration_label ?? '15th May to 18th May (3 days)' }}</p>
                        </div>
                    </div>
                    <div class="vp-prod-card-right">
                        <a href="#" class="vp-btn-chat">Chat</a>
                    </div>
                </div>
            </div>
  <div class="vp-details-section">
                <h3 class="vp-section-card-title">Reference Images</h3>
                <div class="vp-reference-images-gallery">
                    @if(method_exists($booking, 'referenceImages') && count($booking->referenceImages()))
                        @foreach($booking->referenceImages() as $imgUrl)
                            <img src="{{ $imgUrl }}" alt="Reference Visual" class="vp-gallery-thumb">
                        @endforeach
                    @else
                        <img src="http://127.0.0.1:8000/storage/vendors/profile-images/aDGLqDE2ZvU8M5kASSxNHO7lW13tSZERgstsg25T.png" alt="Fallback Preview" class="vp-gallery-thumb" onerror="this.style.display='none';">
                        <div class="vp-gallery-thumb-placeholder"></div>
                    @endif
                </div>
            </div>
            <div class="vp-details-section">
                <h3 class="vp-section-card-title">Custom Notes</h3>
                <div class="vp-custom-notes-box">
                    {{ $booking->customer_notes ?? 'Please ensure the dress is well cleaned and in perfect condition. I usually wear M, but I prefer a slightly loose fit.' }}
                </div>
            </div>

            <div class="vp-details-section">
                <h3 class="vp-section-card-title">Measurements</h3>
                <div class="vp-measurements-pill-grid">
                    @php
                        $measurementData = $booking->measurements ?? [
                            'LEG LOOSE' => '14"', 'KNEES' => '18"', 'SEAT' => '38"', 'HIP' => '40"',
                            'HALF LENGTH' => '20"', 'DOT POINT' => '10"', 'BACK NECK' => '8"', 'FRONT NECK' => '7"',
                            'TOP LENGTH' => '15"', 'ARM HOLE' => '16"', 'WAIST' => '30"', 'THIGH' => '22"',
                            'CHEST' => '34"'
                        ];
                    @endphp

                    @foreach($measurementData as $metricLabel => $metricValue)
                        <div class="vp-measurement-card-pill">
                            <span class="vp-pill-label">{{ strtoupper(str_replace('_', ' ', $metricLabel)) }}</span>
                            <span class="vp-pill-value">{{ $metricValue }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- <div class="vp-details-section vp-status-management-footer-card">
                <form method="POST" action="{{ route('vendor.bookings.status', $booking) }}">
                    @csrf
                    <label class="vp-pill-label" style="margin-bottom: 0.5rem; display: block;">Update Order Status Pathway</label>
                    <div class="vp-footer-inline-actions">
                        <select name="status" class="vp-footer-native-select">
                            @foreach (\App\Models\Order::STATUSES ?? ['new', 'accepted', 'in_transit', 'completed'] as $statusOption)
                                <option value="{{ $statusOption }}" @selected(($booking->status ?? 'new') === $statusOption)>
                                    {{ method_exists('\App\Models\Order', 'statusLabelFor') ? \App\Models\Order::statusLabelFor($statusOption) : ucfirst($statusOption) }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="vp-footer-btn-update">Update Status</button>
                    </div>
                </form>
            </div> -->
        </div>

        <div class="vp-layout-col-right">

            <div class="vp-side-info-card">
                <h4 class="vp-side-card-caption">Customer Detail</h4>
                <div class="vp-side-profile-row">
                    <img src="http://127.0.0.1:8000/storage/vendors/profile-images/aDGLqDE2ZvU8M5kASSxNHO7lW13tSZERgstsg25T.png" alt="Avatar" class="vp-side-avatar" onerror="this.style.display='none';">
                    <div>
                        <div class="vp-side-profile-name">{{ $booking->customer?->name ?? 'Nina Patel' }}</div>
                        <div class="vp-side-profile-sub">{{ $booking->customer?->phone ?? $booking->customer?->mobile ?? '+91 9876543210' }}</div>
                    </div>
                </div>

                <div class="vp-side-address-block">
                    <span class="vp-address-header">SHIPPING ADDRESS</span>
                    <p class="vp-address-body">{{ $booking->delivery_address ?? '123, Palm Grove Society, Bandra West, Mumbai 400050' }}</p>
                </div>
            </div>

            <div class="vp-side-info-card">
                <h4 class="vp-side-card-caption">Delivery Partner</h4>
                <div class="vp-delivery-partner-badge-container">
                    <div class="vp-side-profile-name" style="font-size: 0.95rem;">{{ $booking->delivery_boy?->name ?? 'Ramesh Kumar' }}</div>
                    <div class="vp-side-profile-sub" style="margin-top: 0.15rem;">{{ $booking->delivery_boy?->phone ?? '+91 9876500001' }}</div>
                    <div class="vp-delivery-vehicle-plate">MH-02-AB-1234</div>
                </div>

                <div class="vp-side-address-block" style="margin-top: 1.25rem;">
                    <span class="vp-address-header">BILLING ADDRESS</span>
                    <p class="vp-address-body">{{ $booking->billing_address ?? $booking->delivery_address ?? '123, Palm Grove Society, Bandra West, Mumbai 400050' }}</p>
                </div>
            </div>

            <div class="vp-side-info-card" style="border-bottom: none; box-shadow: none;">
                <h4 class="vp-side-card-caption" style="margin-bottom: 1.25rem;">Payment Summary</h4>

                <div class="vp-financial-ledger">
                    <div class="vp-ledger-row">
                        <span>Sub-Total</span>
                        <span>
                            @if(method_exists($booking, 'subtotal'))
                                ₹{{ number_format($booking->subtotal(), 2) }}
                            @else
                                ₹{{ number_format(($booking->grandTotal() ?? 6200) - 1200, 2) }}
                            @endif
                        </span>
                    </div>
                    <div class="vp-ledger-row">
                        <span>Shipping Fee</span>
                        <span>₹{{ number_format($booking->shipping_fee ?? 200, 2) }}</span>
                    </div>
                    <div class="vp-ledger-row">
                        <span>Platform Fee</span>
                        <span>₹{{ number_format($booking->platform_fee ?? 100, 2) }}</span>
                    </div>
                    <div class="vp-ledger-row">
                        <span>Tax (GST)</span>
                        <span>₹{{ number_format($booking->tax ?? 900, 2) }}</span>
                    </div>

                    <div class="vp-ledger-divider"></div>

                    <div class="vp-ledger-row vp-ledger-total-row">
                        <span>Total Amount</span>
                        <span class="vp-accent-grand-total">₹{{ number_format($booking->grandTotal() ?? 6200, 2) }}</span>
                    </div>

                    <div class="vp-transaction-status-row">
                        <span class="vp-tx-badge vp-tx-paid">PAID</span>
                        <span class="vp-tx-badge vp-tx-method">{{ strtoupper($booking->payment_method ?? 'UPI') }}</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
