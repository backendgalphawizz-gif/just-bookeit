@extends('web.layouts.app')

@section('title', 'Chat')

@section('content')
<div class="jbw-container jbw-page--chat @if($activeChat) jbw-page--chat-active @endif">
    <div class="jbw-page-head jbw-page-head--chat">
        <h1 class="jbw-page-title">Chat</h1>
        <!-- <p class="jbw-page-subtitle">Message designers about outfits, fittings, and bookings</p> -->
    </div>

    <div
        class="jbw-chat-layout"
        x-data="{ sidebarOpen: false }"
        @keydown.escape.window="sidebarOpen = false"
        data-chat-live
        data-poll-url="{{ route('web.chat.poll', [], false) }}"
        data-chat-id="{{ $activeChat?->id }}"
        data-last-message-id="{{ $messages->last()?->id ?? 0 }}"
        data-chat-theme="customer"
        data-chat-search="{{ request('search') }}"
        data-viewer-role="customer"
        data-viewer-id="{{ auth('customer')->id() }}"
    >
        {{-- Mobile drawer backdrop --}}
        <div
            class="jbw-chat-sidebar-backdrop"
            x-show="sidebarOpen"
            x-cloak
            @click="sidebarOpen = false"
            aria-hidden="true"
        ></div>

        {{-- Conversation list --}}
        <aside
            class="jbw-chat-sidebar"
            :class="{ 'jbw-chat-sidebar--mobile-open': sidebarOpen }"
        >
            <p class="jbw-chat-sidebar-title">
                <span>Messages</span>
                <button
                    type="button"
                    class="jbw-chat-sidebar-close"
                    @click="sidebarOpen = false"
                    aria-label="Close messages"
                >&times;</button>
            </p>

            <form method="GET" action="{{ route('web.chat.index') }}" class="jbw-chat-search">
                @if ($activeChat)
                <input type="hidden" name="chat" value="{{ $activeChat->id }}">
                @endif
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search designers…" class="jbw-input">
            </form>

            <div
                class="jbw-chat-threads"
                data-chat-threads
                @click="if ($event.target.closest('a.jbw-chat-thread')) sidebarOpen = false"
            >
                @forelse ($conversations as $conversation)
                @php
                $isActive = $activeChat && $activeChat->id === $conversation->id;
                $vendor = $conversation->vendor;
                $preview = \App\Support\WebChatLivePresenter::threadPreview($conversation->latestMessage);
                @endphp
                <a
                    href="{{ route('web.chat.index', array_filter(['chat' => $conversation->id, 'search' => request('search')]), false) }}"
                    @class(['jbw-chat-thread', 'is-active' => $isActive])
                    data-thread-id="{{ $conversation->id }}"
                >
                    @if ($vendor?->profileImageUrl() || $vendor?->shopLogoUrl())
                    <img src="{{ $vendor->profileImageUrl() ?: $vendor->shopLogoUrl() }}" alt="" class="jbw-chat-thread-avatar">
                    @else
                    <span class="jbw-chat-thread-avatar jbw-chat-thread-avatar--fallback">{{ strtoupper(substr($vendor?->brand_name ?? 'D', 0, 1)) }}</span>
                    @endif
                    <div class="jbw-chat-thread-body">
                        <div class="jbw-chat-thread-top">
                            <strong>{{ $vendor?->brand_name ?? 'Designer' }}</strong>
                            <span>{{ \App\Support\WebChatLivePresenter::threadTime($conversation->last_message_at) }}</span>
                        </div>
                        <p>{{ $preview }}</p>
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
        <div @class(['jbw-chat-main', 'jbw-chat-main--mobile-hide' => ! $activeChat])>
            @if ($activeChat && $activeChat->vendor)
            <div class="jbw-chat-main-head">
                <div class="jbw-chat-main-vendor">
                    <button
                        type="button"
                        class="jbw-chat-menu-btn"
                        @click="sidebarOpen = true"
                        aria-label="Open messages"
                    >
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <line x1="4" y1="6" x2="20" y2="6"></line>
                            <line x1="4" y1="12" x2="20" y2="12"></line>
                            <line x1="4" y1="18" x2="20" y2="18"></line>
                        </svg>
                    </button>

                    @if ($activeChat->vendor->profileImageUrl() || $activeChat->vendor->shopLogoUrl())
                    <img src="{{ $activeChat->vendor->profileImageUrl() ?: $activeChat->vendor->shopLogoUrl() }}" alt="" class="jbw-chat-thread-avatar">
                    @else
                    <span class="jbw-chat-thread-avatar jbw-chat-thread-avatar--fallback">{{ strtoupper(substr($activeChat->vendor->brand_name, 0, 1)) }}</span>
                    @endif
                    <strong>{{ $activeChat->vendor->brand_name }}</strong>
                </div>
                <img src="../../../../assets/frontend/Container.png" alt="">
            </div>

            <div class="jbw-chat-messages" id="jbw-chat-messages" data-chat-messages>
                <div class="jbw-chat-messages-track" data-chat-messages-track>
                    @forelse ($messages as $message)
                    @php
                        $isMine = $message->isFromCustomer();
                        $payload = \App\Support\WebChatLivePresenter::message($message, \App\Models\ChatMessage::SENDER_CUSTOMER);
                    @endphp
                    <div @class([
                        'jbw-chat-message-wrapper',
                        'jbw-chat-message-wrapper--mine' => $isMine,
                        'jbw-chat-message-wrapper--theirs' => ! $isMine,
                    ])
                        data-message-id="{{ $message->id }}"
                    
                    >
                        <div @class([
                            'jbw-chat-bubble',
                            'jbw-chat-bubble--mine' => $isMine,
                            'jbw-chat-bubble--theirs' => ! $isMine,
                        ]) data-chat-bubble>
                            @if ($message->body)
                            <p data-chat-body>{{ $message->body }}</p>
                            @endif

                            @if ($message->attachmentUrl())
                            @include('partials.chat-attachment-media', [
                                'url' => $message->attachmentUrl(),
                                'path' => $message->attachment_path,
                                'type' => $message->attachmentType(),
                                'name' => $message->attachmentDisplayName(),
                                'class' => 'jbw-chat-attachment',
                            ])
                            @endif
                        </div>

                        <div class="jbw-chat-meta">
                            <p class="jbw-chat-time">
                                {{ $message->created_at?->format('g:i A') }}
                                @if ($message->edited_at)
                                    <span class="jbw-chat-edited">· Edited</span>
                                @endif
                            </p>
                            {{-- Edit / Delete temporarily disabled --}}
                        </div>
                    </div>
                    @empty
                    <p class="jbw-chat-empty-thread">Say hello to start the conversation.</p>
                    @endforelse
                </div>
            </div>

            <div class="jbw-chat-compose-stack">
                {{-- Edit banner temporarily disabled with Edit/Delete UI
                <div class="jbw-chat-edit-banner" data-chat-edit-banner hidden>
                    <div class="jbw-chat-edit-banner-copy">
                        <strong>Edit message</strong>
                        <span data-chat-edit-preview></span>
                    </div>
                    <button type="button" class="jbw-chat-edit-banner-close" data-chat-edit-cancel aria-label="Cancel edit">&times;</button>
                </div>
                --}}
                <div class="vp-chat-attach-preview jbw-chat-attach-preview" data-chat-attach-preview hidden>
                    <div class="vp-chat-attach-preview-body" data-chat-attach-preview-body></div>
                    <button type="button" class="vp-chat-attach-preview-clear" data-chat-attach-clear aria-label="Remove attachment">&times;</button>
                </div>
                <form method="POST"
                    action="{{ route('web.chat.messages', $activeChat, false) }}"
                    enctype="multipart/form-data"
                    class="jbw-chat-compose"
                    data-chat-compose>
                    @csrf

                    <label class="jbw-chat-attach" aria-label="Attach file">
                        <input type="file" name="attachment" accept="{{ \App\Support\ChatAttachmentSupport::acceptAttribute() }}" hidden data-chat-attach-input>
                        <span class="jbw-plus-icon"><span>+</span></span>
                    </label>
                    <textarea name="body"
                        rows="1"
                        class="jbw-chat-input"
                        placeholder="Type a message..."
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
            </div>
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

@push('scripts')
@php
    $chatRealtimeViewerRole = 'customer';
    $chatRealtimeViewerId = auth('customer')->id();
@endphp
@include('partials.chat-realtime')
<script src="/js/chat-live.js?v={{ @filemtime(public_path('js/chat-live.js')) }}"></script>
@endpush
@endsection