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
    data-chat-live
    data-poll-url="{{ route('vendor.chat.poll', [], false) }}"
    data-chat-id="{{ $activeChat?->id }}"
    data-last-message-id="{{ $messages->last()?->id ?? 0 }}"
    data-chat-theme="vendor"
    data-chat-search="{{ request('search') }}"
    data-viewer-role="vendor"
    data-viewer-id="{{ auth('vendor')->id() }}"
>
    <aside
        @class(['vp-chat-sidebar', 'vp-chat-sidebar--mobile-hide' => $activeChat])
        data-chat-aside
    >
        @if ($activeChat)
            <div class="vp-chat-sidebar-mobile-head">
                <button type="button" class="vp-chat-sidebar-close" data-chat-aside-close aria-label="Close messages">
                    <svg class="vp-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                <p class="vp-chat-sidebar-title vp-chat-sidebar-title--mobile">Messages</p>
            </div>
            <p class="vp-chat-sidebar-title vp-chat-sidebar-title--desktop-only">Messages</p>
        @else
            <p class="vp-chat-sidebar-title">Messages</p>
        @endif

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
                    $preview = \App\Support\WebChatLivePresenter::threadPreview($conversation->latestMessage);
                @endphp
                <a
                    href="{{ route('vendor.chat.index', array_filter(['chat' => $conversation->id, 'search' => request('search')]), false) }}"
                    @class(['vp-chat-thread', 'is-active' => $isActive])
                    data-thread-id="{{ $conversation->id }}"
                    data-chat-aside-thread
                >
                    @if ($customer?->profileImageUrl())
                        <img src="{{ $customer->profileImageUrl() }}" alt="" class="vp-chat-avatar">
                    @else
                        <span class="vp-chat-avatar vp-chat-avatar--fallback">{{ strtoupper(substr($customer?->name ?? 'C', 0, 1)) }}</span>
                    @endif
                    <div class="vp-chat-thread-body">
                        <div class="vp-chat-thread-top">
                            <strong>{{ $customer?->name ?? 'Customer' }}</strong>
                            <span>{{ \App\Support\WebChatLivePresenter::threadTime($conversation->last_message_at) }}</span>
                        </div>
                        <p>{{ $preview }}</p>
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
                    <button type="button" class="vp-chat-back" data-chat-aside-open aria-label="Open messages">
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
                        @php
                            $isMine = $message->isFromVendor();
                            $payload = \App\Support\WebChatLivePresenter::message($message, \App\Models\ChatMessage::SENDER_VENDOR);
                        @endphp
                        <div @class(['vp-chat-row', 'vp-chat-row--mine' => $isMine, 'vp-chat-row--theirs' => ! $isMine])
                            data-message-id="{{ $message->id }}"
                            @if ($payload['can_edit'] ?? false) data-update-url="{{ $payload['update_url'] }}" @endif
                            @if ($payload['can_delete'] ?? false) data-delete-url="{{ $payload['delete_url'] }}" @endif
                        >
                            <div @class([
                                'vp-chat-bubble',
                                'vp-chat-bubble--mine' => $isMine,
                                'vp-chat-bubble--theirs' => ! $isMine,
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
                                        'class' => 'vp-chat-attachment',
                                    ])
                                @endif
                            </div>
                            <div class="vp-chat-meta">
                                <span class="vp-chat-time">
                                    {{ $message->created_at?->format('g:i A') }}
                                    @if ($message->edited_at)
                                        <span class="vp-chat-edited">· Edited</span>
                                    @endif
                                </span>
                                {{-- Edit / Delete temporarily disabled
                                @if ($isMine)
                                    <div class="vp-chat-message-actions">
                                        @if ($payload['can_edit'] ?? false)
                                            <button type="button" class="vp-chat-action" data-chat-edit>Edit</button>
                                        @endif
                                        <button type="button" class="vp-chat-action vp-chat-action--danger" data-chat-delete>Delete</button>
                                    </div>
                                @endif
                                --}}
                            </div>
                        </div>
                    @empty
                        <p class="vp-chat-empty-thread">No messages yet. Say hello to your customer.</p>
                    @endforelse
                </div>
            </div>

            <div class="vp-chat-compose-stack">
                {{-- Edit banner temporarily disabled with Edit/Delete UI
                <div class="vp-chat-edit-banner" data-chat-edit-banner hidden>
                    <div class="vp-chat-edit-banner-copy">
                        <strong>Edit message</strong>
                        <span data-chat-edit-preview></span>
                    </div>
                    <button type="button" class="vp-chat-edit-banner-close" data-chat-edit-cancel aria-label="Cancel edit">&times;</button>
                </div>
                --}}
                <div class="vp-chat-attach-preview" data-chat-attach-preview hidden>
                    <div class="vp-chat-attach-preview-body" data-chat-attach-preview-body></div>
                    <button type="button" class="vp-chat-attach-preview-clear" data-chat-attach-clear aria-label="Remove attachment">&times;</button>
                </div>
                <form
                    method="POST"
                    action="{{ route('vendor.chat.messages', $activeChat, false) }}"
                    enctype="multipart/form-data"
                    class="vp-chat-compose"
                    data-chat-compose
                >
                    @csrf
                    <label class="vp-chat-attach" aria-label="Attach file">
                        <input type="file" name="attachment" accept="{{ \App\Support\ChatAttachmentSupport::acceptAttribute() }}" hidden data-chat-attach-input>
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
            </div>
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
@php
    $chatRealtimeViewerRole = 'vendor';
    $chatRealtimeViewerId = auth('vendor')->id();
@endphp
@include('partials.chat-realtime')
<script src="/js/chat-live.js?v={{ @filemtime(public_path('js/chat-live.js')) }}"></script>
@endpush
@endsection
