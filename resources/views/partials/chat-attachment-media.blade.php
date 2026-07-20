@php
    $url = $url ?? null;
    $path = $path ?? null;
    $type = $type ?? ($url ? \App\Support\ChatAttachmentSupport::typeFromPath($path) : null);
    $name = $name ?? \App\Support\ChatAttachmentSupport::displayName($path, $label ?? null);
    $class = $class ?? '';
@endphp

@if ($url)
    @if ($type === 'video')
        <video src="{{ $url }}" class="{{ $class }}" controls playsinline preload="metadata"></video>
    @elseif ($type === 'image')
        <img src="{{ $url }}" alt="Attachment" class="{{ $class }} panel-lightbox-trigger">
    @else
        <a href="{{ $url }}" target="_blank" rel="noopener" download class="{{ $class }} {{ $class }}--file vp-chat-file">
            <span class="vp-chat-file-icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                </svg>
            </span>
            <span class="vp-chat-file-meta">
                <span class="vp-chat-file-name">{{ $name }}</span>
                <span class="vp-chat-file-action">Download</span>
            </span>
        </a>
    @endif
@endif
