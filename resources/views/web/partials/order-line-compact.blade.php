@php
    $fallback = $fallback ?? 'https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=300&q=80';
    $meta = collect([
        $category ?? null,
        $variantLabel ?? null,
        isset($quantity) ? 'Qty '.$quantity : null,
        $unitPrice ?? null,
    ])->filter()->implode(' · ');
@endphp

<div class="jbw-order-line">
    <img src="{{ ($image ?? null) ?: $fallback }}" alt="{{ $title ?? 'Item' }}" class="jbw-order-line-img" loading="lazy">
    <div class="jbw-order-line-body">
        <p class="jbw-order-line-title">{{ $title ?? 'Item' }}</p>
        @if ($meta !== '')
            <p class="jbw-order-line-meta">{{ $meta }}</p>
        @endif
    </div>
    @if (!empty($lineTotal))
        <p class="jbw-order-line-price">{{ $lineTotal }}</p>
    @endif
</div>
