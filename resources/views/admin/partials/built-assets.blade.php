@php
    use App\Support\AssetManifest;

    $cssUrl = AssetManifest::url('resources/css/app.css');
@endphp
@if ($cssUrl)
    <link rel="stylesheet" href="{{ $cssUrl }}">
@endif
@include('partials.panel-lightbox-assets')
<script src="{{ asset('js/admin-panel.js') }}" defer></script>
