@php
    use App\Support\AssetManifest;

    $cssUrl = AssetManifest::url('resources/css/app.css');
    $jsUrl = AssetManifest::url('resources/js/app.js');
@endphp
@if ($cssUrl)
    <link rel="stylesheet" href="{{ $cssUrl }}">
@endif
@if ($jsUrl)
    <script src="{{ $jsUrl }}" defer></script>
@endif
