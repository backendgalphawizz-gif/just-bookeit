@extends('web.layouts.app')

@section('title', 'Your cart')

@section('content')
@php $fallbackImg = 'https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=300&q=80'; @endphp

<div class="jbw-container jbw-page-shell">
    <div class="jbw-page-head">
        <!-- <span class="jbw-eyebrow">Shopping bag</span> -->
        <h1 class="jbw-page-title">Your cart</h1>
        <p class="jbw-page-subtitle">{{ $summary['items_count'] ?? 0 }} item{{ ($summary['items_count'] ?? 0) === 1 ? '' : 's' }} from {{ $summary['vendor_count'] ?? 0 }} vendor{{ ($summary['vendor_count'] ?? 0) === 1 ? '' : 's' }}</p>
    </div>

    @if ($items->isEmpty())
        <div class="jbw-cart-empty">
            <svg class="jbw-cart-empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
            </svg>
            <p style="margin:0 0 0.35rem;font-weight:700;font-size:1.125rem">Your cart is empty</p>
            <p style="margin:0 0 1.5rem;color:var(--c-muted)">Discover designer outfits and add them to your bag.</p>
            <a href="{{ route('web.catalog.index') }}" class="jbw-btn jbw-btn--primary">Browse catalog</a>
        </div>
    @else
        <div class="jbw-cart-layout">
            <div class="jbw-cart-items-col">
                @foreach ($items->groupBy('vendor_id') as $vendorItems)
                    @php $vendor = $vendorItems->first()?->vendor; @endphp
                    <section class="jbw-cart-vendor">
                        <header class="jbw-cart-vendor-head">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                            <p class="jbw-cart-vendor-name">{{ $vendor?->brand_name ?? 'Designer' }}</p>
                            <span class="jbw-cart-vendor-count">{{ $vendorItems->sum('quantity') }} item{{ $vendorItems->sum('quantity') === 1 ? '' : 's' }}</span>
                        </header>
                        <div class="jbw-line-item-list">
                            @foreach ($vendorItems as $cartItem)
                                @php
                                    $product = $cartItem->portfolioItem;
                                    $variant = $cartItem->variant;
                                    $variantLabel = $variant ? collect([$variant->size, $variant->color])->filter()->implode(' · ') : null;
                                    $unitRate = $product?->dailyRateFor($variant) ?? 0;
                                    $lineTotal = round($unitRate * $cartItem->quantity, 0);
                                @endphp
                                @include('web.partials.line-item-row', [
                                    'image' => $variant?->imageUrl() ?: $product?->displayImageUrl(),
                                    'fallback' => $fallbackImg,
                                    'title' => $product?->title ?? 'Product',
                                    'category' => $product?->category?->name,
                                    'variantLabel' => $variantLabel,
                                    'showBaseVariant' => $product?->hasVariants() && ! $variant,
                                    'quantity' => $cartItem->quantity,
                                    'unitPrice' => '₹'.number_format($unitRate, 0).' / day',
                                    'lineTotal' => '₹'.number_format($lineTotal, 0),
                                    'actions' => view('web.cart.partials.remove-button', ['cartItem' => $cartItem])->render(),
                                ])
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>

            <aside class="jbw-cart-summary">
                <div class="jbw-overview-card jbw-overview-card--accent">
                    <p class="jbw-overview-label">Order summary</p>
                    <div class="jbw-payment-lines">
                        <div><span>Subtotal (per day)</span><span>₹{{ number_format($summary['subtotal'] ?? 0, 0) }}</span></div>
                        <div><span>Delivery (est.)</span><span>₹{{ number_format($summary['delivery_fee_total'] ?? 0, 0) }}</span></div>
                    </div>
                    <p class="jbw-cart-summary-note">Rental total is calculated at checkout based on your selected dates.</p>
                    <a href="{{ route('web.checkout.show') }}" class="jbw-btn jbw-btn--primary jbw-btn--block" style="margin-top:1.25rem">
                        Proceed to checkout
                    </a>
                    <a href="{{ route('web.catalog.index') }}" class="jbw-btn jbw-btn--outline jbw-btn--block" style="margin-top:0.5rem">Continue shopping</a>
                </div>
            </aside>
        </div>
    @endif
</div>
@endsection
