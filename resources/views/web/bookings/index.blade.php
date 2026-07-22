@extends('web.layouts.profile')

@section('title', 'Booking History')

@section('content')
@php
    $fallbackImg = 'https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=300&q=80';

    $bookingTypeLabel = function ($category = null, ?string $fallback = null): string {
        $slug = strtolower((string) ($category?->slug ?? ''));
        $name = strtolower((string) ($category?->name ?? ''));
        $haystack = $slug.' '.$name.' '.strtolower((string) $fallback);

        if (str_contains($haystack, 'jewellery') || str_contains($haystack, 'jewelry')) {
            return 'Rented Jewellery';
        }
        if (str_contains($haystack, 'dress') || str_contains($haystack, 'rental')) {
            return 'Rented Dress';
        }
        if (str_contains($haystack, 'fashion') || str_contains($haystack, 'designer')) {
            return 'Designing';
        }

        return $category?->name ?: ($fallback ?: 'Booking');
    };

    $statusMeta = function (string $status): array {
        $class = match ($status) {
            'new', 'pending_acceptance' => 'pending',
            'processing', 'partially_delivered', 'in_progress', 'accepted' => 'in_progress',
            'completed', 'delivered' => 'delivered',
            'cancelled', 'refunded', 'partially_cancelled' => 'cancelled',
            default => 'default',
        };

        $label = match ($class) {
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            default => str_replace('_', ' ', ucfirst($status)),
        };

        return [$class, $label];
    };

    $linesFromOrder = function ($order) use ($bookingTypeLabel, $fallbackImg): array {
        $type = $bookingTypeLabel($order->category, $order->orderTypeLabel());
        $items = $order->orderItems;

        if ($items && $items->isNotEmpty()) {
            return $items->map(function ($item) use ($order, $type, $fallbackImg) {
                return [
                    'title' => $item->title() ?: $order->itemDisplayName(),
                    'image' => $item->displayImageUrl() ?: $order->itemImageUrl() ?: $fallbackImg,
                    'type' => $type,
                ];
            })->all();
        }

        return [[
            'title' => $order->itemDisplayName(),
            'image' => $order->itemImageUrl() ?: $fallbackImg,
            'type' => $type,
        ]];
    };
@endphp

<div class="jbw-card jbw-booking-history jbw-profile-panel">
    <div class="jbw-profile-panel-head">
        <h2 class="jbw-profile-panel-title">Booking History</h2>
        <p class="jbw-profile-panel-sub">View and manage your past and upcoming dress rentals.</p>
    </div>

    <div class="jbw-booking-list">
        @forelse ($orders as $entry)
            @if ($entry['kind'] === 'checkout')
                @php
                    $checkout = $entry['checkout'];
                    [$statusClass, $statusLabel] = $statusMeta((string) $checkout->status);
                    $lines = collect($checkout->subOrders)->flatMap(fn ($sub) => $linesFromOrder($sub))->values()->all();
                    $bookId = $checkout->order_number;
                    $bookedAt = $checkout->created_at;
                    $total = (float) $checkout->grand_total;
                    $detailsUrl = route('web.bookings.checkout.show', $checkout);
                @endphp
            @else
                @php
                    $order = $entry['order'];
                    [$statusClass, $statusLabel] = $statusMeta((string) $order->status);
                    $lines = $linesFromOrder($order);
                    $bookId = $order->order_number;
                    $bookedAt = $order->created_at;
                    $total = (float) $order->grandTotal();
                    $detailsUrl = route('web.bookings.show', $order);
                @endphp
            @endif

            <article class="jbw-bh-card">
                <header class="jbw-bh-card-head">
                    <p class="jbw-bh-card-id">#{{ $bookId }}</p>
                    <span class="jbw-bh-status jbw-bh-status--{{ $statusClass }}">{{ $statusLabel }}</span>
                </header>
                <p class="jbw-bh-card-date">{{ $bookedAt?->format('d M Y, g:i A') }}</p>

                <div class="jbw-bh-items">
                    @foreach ($lines as $line)
                        <div class="jbw-bh-item">
                            <img src="{{ $line['image'] }}" alt="{{ $line['title'] }}" class="jbw-bh-item-img" loading="lazy">
                            <p class="jbw-bh-item-title">{{ $line['title'] }}</p>
                            <span class="jbw-bh-item-type">{{ $line['type'] }}</span>
                        </div>
                    @endforeach
                </div>

                <footer class="jbw-bh-card-foot">
                    <div class="jbw-bh-total">
                        <span class="jbw-bh-total-label">TOTAL AMOUNT</span>
                        <strong class="jbw-bh-total-value">₹{{ number_format($total, 0) }}</strong>
                    </div>
                    <a href="{{ $detailsUrl }}" class="jbw-bh-details">
                        View Details
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
                    </a>
                </footer>
            </article>
        @empty
            @php
                $emptyCopy = [
                    'title' => 'Your booking story starts here',
                    'text' => 'No bookings yet. Discover designer outfits, dresses, and jewellery — your history will live in this space.',
                ];
            @endphp
            <div class="jbw-booking-empty">
                <div class="jbw-booking-empty-art" aria-hidden="true">
                    <img src="{{ asset('assets/frontend/empty-bookings.svg') }}" alt="" width="280" height="210">
                </div>
                <h2 class="jbw-booking-empty-title">{{ $emptyCopy['title'] }}</h2>
                <p class="jbw-booking-empty-text">{{ $emptyCopy['text'] }}</p>
                <a href="{{ route('web.catalog.index') }}" class="jbw-btn jbw-btn--primary jbw-btn--sm">Browse outfits</a>
            </div>
        @endforelse

        @if ($orders->hasPages())
            <div class="jbw-booking-list-pagination">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
@endsection
