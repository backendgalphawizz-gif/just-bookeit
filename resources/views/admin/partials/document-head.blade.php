@php
    $branding = $branding ?? [];
    $documentTitle = $branding['name'] ?? \App\Models\PlatformSetting::get('platform_name', config('app.name', 'Just Book IT'));
    $faviconUrl = $branding['logo_url'] ?? asset('images/just-book-it-logo.png');
@endphp
<title>{{ $documentTitle }}</title>
<link rel="icon" type="image/png" href="{{ $faviconUrl }}">
