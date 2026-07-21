@php
    $fallback = $fallback ?? 'https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=300&q=80';
    $img = $image ?? null;
    $qty = (int) ($quantity ?? 1);
@endphp

<article class="jbw-line-item{{ !empty($compact) ? ' jbw-line-item--compact' : '' }}">
    <div class="jbw-line-item-media">
        <img src="{{ $img ?: $fallback }}" alt="{{ $title ?? 'Product' }}" class="jbw-line-item-img" loading="lazy">
    </div>
    <div class="jbw-line-item-body">
        @if (!empty($brand))
            <p class="jbw-line-item-brand">{{ $brand }}</p>
        @endif
        <h3 class="jbw-line-item-title">{{ $title ?? 'Product' }}</h3>
        <dl class="jbw-line-item-details">
            @if (!empty($category))
                <div><dt>Category</dt><dd>{{ $category }}</dd></div>
            @endif
            @if (!empty($variantLabel))
                <div><dt>Variant</dt><dd>{{ $variantLabel }}</dd></div>
            @elseif (!empty($showBaseVariant))
                <div><dt>Variant</dt><dd>Base item</dd></div>
            @endif
            <div><dt>Quantity</dt><dd>{{ $qty }}</dd></div>
            @if (!empty($unitPrice))
                <div><dt>Rate</dt><dd>{{ $unitPrice }}</dd></div>
            @endif
            @if (!empty($lineTotal))
                <div><dt>Line total</dt><dd class="jbw-line-item-total">{{ $lineTotal }}</dd></div>
            @endif
        </dl>
    </div>
    @if (!empty($actions))
        <div class="jbw-line-item-actions">{!! $actions !!}</div>
    @endif
</article>
