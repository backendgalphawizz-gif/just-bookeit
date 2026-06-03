@php
    use App\Support\AssetManifest;

    $cssUrl = AssetManifest::url('resources/css/app.css');
@endphp
@if ($cssUrl)
    <link rel="stylesheet" href="{{ $cssUrl }}">
@endif
<script src="{{ asset('js/admin-panel.js') }}" defer></script>
