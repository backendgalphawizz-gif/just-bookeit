@php
    $href = $banner->redirect_url ?: route('vendor.bookings.index');
@endphp
<a href="{{ $href }}" class="vp-promo-banner" @if($banner->redirect_url) target="_blank" rel="noopener" @endif>
    @if ($banner->image_url)
        <img src="{{ $banner->image_url }}" alt="" class="vp-promo-banner__img">
    @endif
    <div class="vp-promo-banner__body">
        <p class="vp-promo-banner__title">{{ $banner->title }}</p>
        @if ($banner->subtitle)
            <p class="vp-promo-banner__sub">{{ $banner->subtitle }}</p>
        @endif
    </div>
</a>
