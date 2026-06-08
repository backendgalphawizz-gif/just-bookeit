@php
    $logoUrl = $vendorBranding['logo_url'] ?? null;
    $logoSrc = $logoUrl ?: asset('images/just-book-it-logo.png');
    $logoAlt = $vendorBranding['name'] ?? 'Just Book IT';
@endphp

<a href="{{ route('vendor.dashboard') }}" class="vp-brand-card" aria-label="{{ $logoAlt }} — Vendor dashboard">
    <img src="{{ $logoSrc }}" alt="{{ $logoAlt }}" class="vp-brand-img" decoding="async">
</a>
