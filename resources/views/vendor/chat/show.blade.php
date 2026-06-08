@extends('vendor.layouts.app')

@section('title', 'Chat')

@section('content')
<a href="{{ route('vendor.chat.index') }}" class="vp-back-link">← Back to messages</a>

<div class="vp-page-head">
    <div>
        <h1 class="vp-page-title">{{ $chat->customer?->name ?? 'Customer' }}</h1>
        <p class="vp-page-sub">{{ $chat->customer?->mobile }}</p>
    </div>
</div>

<div class="vp-card">
    <div class="vp-chat-thread">
        @forelse ($messages as $message)
            <div class="vp-msg {{ $message->sender_type === 'vendor' ? 'vp-msg--mine' : 'vp-msg--theirs' }}">
                {{ $message->body }}
                @if ($message->attachmentUrl())
                    <div style="margin-top:.5rem;"><img src="{{ $message->attachmentUrl() }}" alt="Attachment" class="panel-lightbox-trigger" style="max-width:200px;border-radius:8px;"></div>
                @endif
                <div class="vp-msg-time">{{ $message->created_at?->format('g:i A') }}</div>
            </div>
        @empty
            <p class="vp-empty">No messages yet. Start the conversation below.</p>
        @endforelse
    </div>
    @if ($messages->hasPages())
        <div class="vp-card-pad">{{ $messages->links('vendor.pagination.default') }}</div>
    @endif
</div>

<form method="POST" action="{{ route('vendor.chat.messages', $chat) }}" enctype="multipart/form-data" class="vp-card vp-card-pad" style="margin-top:1rem;">
    @csrf
    <div class="vp-field" style="margin-bottom:.75rem;">
        <textarea name="body" class="vp-textarea" rows="2" placeholder="Type a message..."></textarea>
    </div>
    <div class="vp-actions">
        <input type="file" name="attachment" class="vp-file" accept="image/*">
        <button type="submit" class="vp-btn vp-btn--primary">Send</button>
    </div>
</form>
@endsection
