@extends('vendor.layouts.app')

@section('title', 'Chat')

@section('content')
<!-- <a href="{{ route('vendor.chat.index') }}" class="vp-back-link">← Back to messages</a> -->

<!-- <div class="vp-page-head">
    <div>
        <h1 class="vp-page-title">{{ $chat->customer?->name ?? 'Customer' }}</h1>
        <p class="vp-page-sub">{{ $chat->customer?->mobile }}</p>
    </div>
</div> -->
<div class="jbw-container">
    <div class="jbw-chat-layout ">
        {{-- Conversation list --}}
        <aside class="jbw-chat-sidebar vp-card">
            <p class="messagejnd">Messages</p>
            <div class="">

<form method="GET" action="#" class="jbw-chat-search jdv">
    <input type="search"
           name="search"
           value="Elegant Couture"
           placeholder="Search designers…"
           class="jbw-input jv-input"
           list="designer-list">


</form>
            </div>

            <div class="jbw-chat-threads">

                <a href="#" class="jbw-chat-thread is-active">
                    <span class="jbw-chat-thread-avatar jbw-chat-thread-avatar--fallback">E</span>
                    <div class="jbw-chat-thread-body">
                        <div class="jbw-chat-thread-top">
                            <strong>Elegant Couture</strong>
                            <span>10:45 AM</span>
                        </div>
                        <p>Hello! How can I help you today?</p>
                    </div>
                </a>

                <a href="#" class="jbw-chat-thread">
                    <span class="jbw-chat-thread-avatar jbw-chat-thread-avatar--fallback">R</span>
                    <div class="jbw-chat-thread-body">
                        <div class="jbw-chat-thread-top">
                            <strong>Royal Designers</strong>
                            <span>9:30 AM</span>
                        </div>
                        <p>Your order is ready for pickup.</p>
                    </div>
                </a>

                <a href="#" class="jbw-chat-thread">
                    <span class="jbw-chat-thread-avatar jbw-chat-thread-avatar--fallback">F</span>
                    <div class="jbw-chat-thread-body">
                        <div class="jbw-chat-thread-top">
                            <strong>Fashion Hub</strong>
                            <span>Yesterday</span>
                        </div>
                        <p>Please share your measurements.</p>
                    </div>
                </a>

                <a href="#" class="jbw-chat-thread">
                    <span class="jbw-chat-thread-avatar jbw-chat-thread-avatar--fallback">W</span>
                    <div class="jbw-chat-thread-body">
                        <div class="jbw-chat-thread-top">
                            <strong>Wedding Studio</strong>
                            <span>Mon</span>
                        </div>
                        <p>New bridal collection is available.</p>
                    </div>
                </a>

            </div>
        </aside>

        <div class="jbw-chat-main">

            <div class="vp-card">
                <div class="vp-page-heads">
                    <div>
                        <h1 class="vp-page-title vptitle">{{ $chat->customer?->name ?? 'Customer' }}</h1>
                        <!-- <p class="vp-page-sub">{{ $chat->customer?->mobile }}</p> -->
                    </div>
                    <img src="../../../../assets/frontend/Container.png" />
                </div>
                <div class="vp-chat-thread">
                    @forelse ($messages as $message)
                    <div class="vp-msg {{ $message->sender_type === 'vendor' ? 'vp-msg--mine' : 'vp-msg--theirs' }}">
                        {{ $message->body }}
                        @if ($message->attachmentUrl())
                        <div class="vp-msg-image">
                            <img src="{{ $message->attachmentUrl() }}" alt="Attachment" class="panel-lightbox-trigger" style="max-width:200px;border-radius:8px;">
                        </div>
                        @endif
                        <div class="vp-msg-time {{ $message->sender_type === 'vendor' ? 'vp-msg-time--mine' : 'vp-msg-time--theirs' }}">
                            {{ $message->created_at?->format('g:i A') }}
                        </div>
                        <!-- <div class="vp-msg-time">{{ $message->created_at?->format('g:i A') }}</div> -->
                    </div>
                    @empty
                    <p class="vp-empty">No messages yet. Start the conversation below.</p>
                    @endforelse
                </div>
                @if ($messages->hasPages())
                <div class="vp-card-pad">{{ $messages->links('vendor.pagination.default') }}</div>
                @endif
                <!-- </div> -->

                <!-- <form method="POST" action="{{ route('vendor.chat.messages', $chat) }}" enctype="multipart/form-data" class="vp-card vp-card-pad" style="margin-top:1rem;">
    @csrf
    <div class="vp-field" style="margin-bottom:.75rem;">
        <textarea name="body" class="vp-textarea" rows="2" placeholder="Type a message..."></textarea>
    </div>
    <div class="vp-actions">
        <input type="file" name="attachment" class="vp-file" accept="image/*">
        <button type="submit" class="vp-btn vp-btn--primary">Send</button>
    </div>
</form> -->
                <form method="POST"
                    action="{{ route('vendor.chat.messages', $chat) }}"
                    enctype="multipart/form-data"
                    class="vp-chat-compose">
                    @csrf

                    <label class="vp-chat-attach">
                        <input type="file" name="attachment" accept="image/*">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8" />
                            <path d="M12 8v8M8 12h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        </svg>
                    </label>

                    <textarea name="body"
                        class="vp-chat-input"
                        rows="1"
                        placeholder="Type a message..."></textarea>

                    <button type="submit" class="vp-chat-send">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="transform: rotate(45deg);">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>


@endsection
