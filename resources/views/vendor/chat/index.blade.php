@extends('vendor.layouts.app')

@section('title', 'Chat')

@section('content')
<div @class(['vp-page--chat', 'vp-page--chat-active' => $activeChat])>
<div class="vp-page-head vp-page-head--compact">
    <div>
        <h1 class="vp-page-title">Chat</h1>
    </div>
</div>
 
<div
    class="vp-chat-layout"
    x-data="{ asideOpen: false }"
    :class="{ 'vp-chat-layout--aside-open': asideOpen }"
    @keydown.escape.window="asideOpen = false"
    data-chat-live
    data-poll-url="{{ route('vendor.chat.poll', [], false) }}"
    data-chat-id="{{ $activeChat?->id }}"
    data-last-message-id="{{ $messages->last()?->id ?? 0 }}"
    data-chat-theme="vendor"
    data-chat-search="{{ request('search') }}"
>
    <aside
        @class(['vp-chat-sidebar', 'vp-chat-sidebar--mobile-hide' => $activeChat])
        :class="{ 'vp-chat-sidebar--mobile-open': asideOpen }"
    >
        @if ($activeChat)
            <div class="vp-chat-sidebar-mobile-head">
                <button type="button" class="vp-chat-sidebar-close" @click="asideOpen = false" aria-label="Close messages">
                    <svg class="vp-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                <p class="vp-chat-sidebar-title vp-chat-sidebar-title--mobile">Messages</p>
            </div>
        @endif
        <p @class(['vp-chat-sidebar-title', 'vp-chat-sidebar-title--desktop' => $activeChat])>Messages</p>

        <form method="GET" action="{{ route('vendor.chat.index') }}" class="vp-chat-search">
            @if ($activeChat)
                <input type="hidden" name="chat" value="{{ $activeChat->id }}">
            @endif
            <input
                type="search"
                name="search"
                value="{{ request('search') }}"
                class="vp-input"
                placeholder="Search customers…"
            >
        </form>

        <div class="vp-chat-threads" data-chat-threads>
            @forelse ($conversations as $conversation)
                @php
                    $isActive = $activeChat && $activeChat->id === $conversation->id;
                    $customer = $conversation->customer;
                    $preview = $conversation->latestMessage?->body ?? 'No messages yet';
                @endphp
                <a
                    href="{{ route('vendor.chat.index', array_filter(['chat' => $conversation->id, 'search' => request('search')]), false) }}"
                    @class(['vp-chat-thread', 'is-active' => $isActive])
                    @click="asideOpen = false"
                >
                    @if ($customer?->profileImageUrl())
                        <img src="{{ $customer->profileImageUrl() }}" alt="" class="vp-chat-avatar">
                    @else
                        <span class="vp-chat-avatar vp-chat-avatar--fallback">{{ strtoupper(substr($customer?->name ?? 'C', 0, 1)) }}</span>
                    @endif
                    <div class="vp-chat-thread-body">
                        <div class="vp-chat-thread-top">
                            <strong>{{ $customer?->name ?? 'Customer' }}</strong>
                            <span>{{ $conversation->last_message_at?->format('g:i A') }}</span>
                        </div>
                        <p>{{ \Illuminate\Support\Str::limit($preview, 52) }}</p>
                    </div>
                </a>
            @empty
                <div class="vp-chat-empty-sidebar">
                    <p>No conversations yet.</p>
                </div>
            @endforelse
        </div>
    </aside>

    <div @class(['vp-chat-main', 'vp-chat-main--mobile-hide' => ! $activeChat])>
        @if ($activeChat && $activeChat->customer)
            <div class="vp-chat-main-head">
                <div class="vp-chat-main-user">
                    <button type="button" class="vp-chat-back" @click="asideOpen = true" aria-label="Open messages">
                        <svg class="vp-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
                        </svg>
                    </button>
                    @if ($activeChat->customer->profileImageUrl())
                        <img src="{{ $activeChat->customer->profileImageUrl() }}" alt="" class="vp-chat-avatar">
                    @else
                        <span class="vp-chat-avatar vp-chat-avatar--fallback">{{ strtoupper(substr($activeChat->customer->name, 0, 1)) }}</span>
                    @endif
                    <div>
                        <strong>{{ $activeChat->customer->name }}</strong>
                        @if ($activeChat->customer->mobile)
                            <span class="vp-chat-main-sub">{{ $activeChat->customer->mobile }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="vp-chat-messages" id="vp-chat-messages" data-chat-messages>
                <div class="vp-chat-messages-track" data-chat-messages-track>
                    @forelse ($messages as $message)
                        <div @class(['vp-chat-row', 'vp-chat-row--mine' => $message->sender_type === 'vendor']) data-message-id="{{ $message->id }}">
                            <div @class([
                                'vp-chat-bubble',
                                'vp-chat-bubble--mine' => $message->sender_type === 'vendor',
                                'vp-chat-bubble--theirs' => $message->sender_type !== 'vendor',
                            ])>
                                @if ($message->body)
                                    <p>{{ $message->body }}</p>
                                @endif
                                @if ($message->attachmentUrl())
                                    @include('partials.chat-attachment-media', [
                                        'url' => $message->attachmentUrl(),
                                        'path' => $message->attachment_path,
                                        'type' => $message->attachmentType(),
                                        'class' => 'vp-chat-attachment',
                                    ])
                                @endif
                            </div>
                            <span class="vp-chat-time">{{ $message->created_at?->format('g:i A') }}</span>
                        </div>
                    @empty
                        <p class="vp-chat-empty-thread">No messages yet. Say hello to your customer.</p>
                    @endforelse
                </div>
            </div>

            <form
                method="POST"
                action="{{ route('vendor.chat.messages', $activeChat, false) }}"
                enctype="multipart/form-data"
                class="vp-chat-compose"
                data-chat-compose
            >
                @csrf
                <label class="vp-chat-attach" aria-label="Attach image or video">
                    <input type="file" name="attachment" accept="{{ \App\Support\ChatAttachmentSupport::acceptAttribute() }}" hidden>
                    <span class="vp-chat-attach-icon">+</span>
                </label>
                <textarea
                    name="body"
                    rows="1"
                    class="vp-chat-input"
                    placeholder="Type a message..."
                    data-chat-input
                >{{ old('body') }}</textarea>
                <button type="submit" class="vp-chat-send" aria-label="Send message">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </form>
        @else
            <div class="vp-chat-main-empty">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z" />
                </svg>
                <p>Select a conversation to start messaging.</p>
            </div>
        @endif
    </div>
</div>
</div>

@push('scripts')
<script src="/js/chat-live.js?v={{ @filemtime(public_path('js/chat-live.js')) }}"></script>
@endpush
@endsection
