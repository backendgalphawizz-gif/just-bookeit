@php
    $url = $url ?? null;
    $type = $type ?? ($url ? \App\Support\ChatAttachmentSupport::typeFromPath($path ?? null) : null);
    $class = $class ?? '';
@endphp

@if ($url)
    @if ($type === 'video')
        <video src="{{ $url }}" class="{{ $class }}" controls playsinline preload="metadata"></video>
    @elseif ($type === 'image')
        <img src="{{ $url }}" alt="Attachment" class="{{ $class }} panel-lightbox-trigger">
    @else
        <a href="{{ $url }}" target="_blank" rel="noopener" class="{{ $class }}">View attachment</a>
    @endif
@endif
