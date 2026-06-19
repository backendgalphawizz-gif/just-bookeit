@extends('web.layouts.app')

@section('title', 'Chat')

@section('content')
<div class="jbw-container">
    <div class="jbw-page-head" style="padding-top:0rem; margin-bottom:0rem;">
        <h1 class="jbw-page-title">Chat</h1>
        <!-- <p class="jbw-page-subtitle">Message designers about outfits, fittings, and bookings</p> -->
    </div>

    <div
        class="jbw-chat-layout"
        data-chat-live
        data-poll-url="{{ route('web.chat.poll', [], false) }}"
        data-chat-id="{{ $activeChat?->id }}"
        data-last-message-id="{{ $messages->last()?->id ?? 0 }}"
        data-chat-theme="customer"
        data-chat-search="{{ request('search') }}"
    >
        {{-- Conversation list --}}
        <aside class="jbw-chat-sidebar">
            <p class="jbw-chat-sidebar-title">Messages</p>
            <form method="GET" action="{{ route('web.chat.index') }}" class="jbw-chat-search">
                @if ($activeChat)
                <input type="hidden" name="chat" value="{{ $activeChat->id }}">
                @endif
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search designers…" class="jbw-input">
            </form>

            <div class="jbw-chat-threads" data-chat-threads>
                @forelse ($conversations as $conversation)
                @php
                $isActive = $activeChat && $activeChat->id === $conversation->id;
                $vendor = $conversation->vendor;
                $preview = $conversation->latestMessage?->body ?? 'No messages yet';
                @endphp
                <a
                    href="{{ route('web.chat.index', array_filter(['chat' => $conversation->id, 'search' => request('search')]), false) }}"
                    @class(['jbw-chat-thread', 'is-active'=> $isActive])
                    >
                    @if ($vendor?->profileImageUrl() || $vendor?->shopLogoUrl())
                    <img src="{{ $vendor->profileImageUrl() ?: $vendor->shopLogoUrl() }}" alt="" class="jbw-chat-thread-avatar">
                    @else
                    <span class="jbw-chat-thread-avatar jbw-chat-thread-avatar--fallback">{{ strtoupper(substr($vendor?->brand_name ?? 'D', 0, 1)) }}</span>
                    @endif
                    <div class="jbw-chat-thread-body">
                        <div class="jbw-chat-thread-top">
                            <strong>{{ $vendor?->brand_name ?? 'Designer' }}</strong>
                            <span>{{ $conversation->last_message_at?->format('g:i A') ?? '' }}</span>
                        </div>
                        <p>{{ \Illuminate\Support\Str::limit($preview, 52) }}</p>
                    </div>
                </a>
                @empty
                <div class="jbw-chat-empty-sidebar">
                    <p>No conversations yet.</p>
                    <a href="{{ route('web.catalog.index') }}" class="jbw-btn jbw-btn--outline jbw-btn--sm">Browse catalog</a>
                </div>
                @endforelse
            </div>
        </aside>

        {{-- Active thread --}}
        <div class="jbw-chat-main">
            @if ($activeChat && $activeChat->vendor)
            <div class="jbw-chat-main-head">
                <div class="jbw-chat-main-vendor">
                    @if ($activeChat->vendor->profileImageUrl() || $activeChat->vendor->shopLogoUrl())
                    <img src="{{ $activeChat->vendor->profileImageUrl() ?: $activeChat->vendor->shopLogoUrl() }}" alt="" class="jbw-chat-thread-avatar">
                    @else
                    <span class="jbw-chat-thread-avatar jbw-chat-thread-avatar--fallback">{{ strtoupper(substr($activeChat->vendor->brand_name, 0, 1)) }}</span>
                    @endif
                    <strong>{{ $activeChat->vendor->brand_name }}</strong>
                </div>
                <!-- <a href="{{ route('web.vendors.show', $activeChat->vendor) }}" class="jbw-btn jbw-btn--outline jbw-btn--sm">View profile </a> -->
                <img src="../../../../assets/frontend/Container.png"/>
            </div>

            <div class="jbw-chat-messages" id="jbw-chat-messages" data-chat-messages>
                @forelse ($messages as $message)
                <div @class([
                    'jbw-chat-message-wrapper',
                    'jbw-chat-message-wrapper--mine' => $message->isFromCustomer(),
                    'jbw-chat-message-wrapper--theirs' => ! $message->isFromCustomer(),
                ]) data-message-id="{{ $message->id }}">
                    <div @class([ 'jbw-chat-bubble' , 'jbw-chat-bubble--mine'=> $message->isFromCustomer(),
                        'jbw-chat-bubble--theirs' => ! $message->isFromCustomer()
                        ])>
                        @if ($message->body)
                        <p>{{ $message->body }}</p>
                        @endif

                        @if ($message->attachmentUrl())
                        @include('partials.chat-attachment-media', [
                            'url' => $message->attachmentUrl(),
                            'path' => $message->attachment_path,
                            'type' => $message->attachmentType(),
                            'class' => 'jbw-chat-attachment',
                        ])
                        @endif
                    </div>

                    <p class="jbw-chat-time">{{ $message->created_at?->format('g:i A') }}</p>
                </div>
                @empty
                <p class="jbw-chat-empty-thread">Say hello to start the conversation.</p>
                @endforelse
            </div>

            <form method="POST"
                action="{{ route('web.chat.messages', $activeChat, false) }}"
                enctype="multipart/form-data"
                class="jbw-chat-compose"
                data-chat-compose>
                @csrf

                <label class="jbw-chat-attach" aria-label="Attach image or video">
                    <input type="file" name="attachment" accept="{{ \App\Support\ChatAttachmentSupport::acceptAttribute() }}" hidden>
                    <span class="jbw-plus-icon"><span>+</span></span>
                </label>
                <textarea name="body"
                    rows="1"
                    class="jbw-chat-input"
                    placeholder="Type a message... (Enter to send, Shift+Enter for new line)"
                    data-chat-input>{{ old('body') }}</textarea>

                <button type="submit" class="jbw-chat-send">
                    <svg width="17" height="17"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        style="transform: rotate(45deg);">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </form>
            @else
            <div class="jbw-chat-main-empty">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z" />
                </svg>
                <p>Select a conversation or start chatting from a designer profile.</p>
                <a href="{{ route('web.catalog.index') }}" class="jbw-btn jbw-btn--primary">Browse designers</a>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- @if ($activeChat)
<script>
(function () {
    const box = document.getElementById('jbw-chat-messages');
    if (!box) return;
    const scroll = function () { box.scrollTop = box.scrollHeight; };
    scroll();
    document.addEventListener('DOMContentLoaded', scroll);
    window.addEventListener('load', scroll);
})();
</script>
@endif -->
@if ($activeChat)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const box = document.getElementById('jbw-chat-messages');

    if (box) {
        setTimeout(() => {
            box.scrollTop = box.scrollHeight;
        }, 100);
    }
});
</script>
@endif
@endsection
