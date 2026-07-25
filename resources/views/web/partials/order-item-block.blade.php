@php
    $fallback = $fallback ?? 'https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=300&q=80';
    $meta = collect([
        $category ?? null,
        $variantLabel ?? null,
        isset($quantity) ? 'Qty '.$quantity : null,
        $unitPrice ?? null,
    ])->filter()->implode(' · ');
    $orderItem = $orderItem ?? null;
    $order = $order ?? null;
@endphp

<article class="jbw-order-item-block">
    <div class="jbw-order-line">
        <img src="{{ ($image ?? null) ?: $fallback }}" alt="{{ $title ?? 'Item' }}" class="jbw-order-line-img" loading="lazy">
        <div class="jbw-order-line-body">
            <p class="jbw-order-line-title">{{ $title ?? 'Item' }}</p>
            @if ($meta !== '')
                <p class="jbw-order-line-meta">{{ $meta }}</p>
            @endif
            @if ($orderItem && (float) ($orderItem->damage_amount ?? 0) > 0)
                <p class="jbw-order-line-meta jbw-order-line-meta--damage">
                    Damage deduction: ₹{{ number_format((float) $orderItem->damage_amount, 0) }}
                    @if ($orderItem->damage_note)
                        · {{ $orderItem->damage_note }}
                    @endif
                </p>
            @endif
        </div>
        @if (!empty($lineTotal))
            <p class="jbw-order-line-price">{{ $lineTotal }}</p>
        @endif
    </div>

    @if ($order)
        @include('web.partials.order-item-progress-inline', [
            'order' => $order,
            'orderItem' => $orderItem,
            'compact' => true,
        ])
    @endif

    @if ($order)
        @include('web.partials.booking-item-actions', [
            'order' => $order,
            'orderItem' => $orderItem,
        ])
    @endif
</article>
