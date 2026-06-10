@php
    use App\Support\AssetManifest;
    use App\Support\UploadLimits;

    $cssUrl = AssetManifest::url('resources/css/app.css');
@endphp
<meta name="jb-post-max-bytes" content="{{ UploadLimits::safeMultipartUploadBytes() }}">
<meta name="jb-per-file-max-bytes" content="{{ UploadLimits::perFileMaxBytes() }}">
@if ($cssUrl)
    <link rel="stylesheet" href="{{ $cssUrl }}">
@endif
@include('partials.panel-lightbox-assets')
<script src="{{ asset('js/admin-panel.js') }}" defer></script>
