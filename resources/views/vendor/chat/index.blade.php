@extends('vendor.layouts.app')

@section('title', 'Chat')

@section('content')
<div class="vp-page-head">
    <div>
        <h1 class="vp-page-title">Chat</h1>
        <p class="vp-page-sub">Message your customers in real time</p>
    </div>
</div>

@push('filter_actions')
    <x-vendor.export-dropdown module="chat" :params="['search']" />
@endpush

<form method="GET" class="vp-filters">
    <div class="vp-filters-grid">
        <div class="vp-filters-field vp-filters-field--wide">
            <label class="vp-label" for="chat-search">Search</label>
            <input type="text" id="chat-search" name="search" value="{{ request('search') }}" class="vp-input" placeholder="Customer name...">
        </div>
        @include('vendor.partials.filters-end', ['resetUrl' => route('vendor.chat.index')])
    </div>
</form>

<div class="vp-card">
    <div class="vp-card-count">{{ $conversations->total() }} conversations</div>
    @forelse ($conversations as $chat)
        <a href="{{ route('vendor.chat.show', $chat) }}" class="vp-chat-item">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;">
                <div>
                    <strong>{{ $chat->customer?->name ?? 'Customer' }}</strong>
                    <div style="font-size:.82rem;color:var(--vp-muted);margin-top:.25rem;">
                        {{ \Illuminate\Support\Str::limit($chat->latestMessage?->body ?? 'No messages yet', 60) }}
                    </div>
                </div>
                <div style="font-size:.75rem;color:var(--vp-muted);white-space:nowrap;">{{ $chat->last_message_at?->diffForHumans() }}</div>
            </div>
        </a>
    @empty
        <div class="vp-empty">No conversations yet.</div>
    @endforelse
    @if ($conversations->hasPages())
        <div class="vp-card-pad">{{ $conversations->links('vendor.pagination.default') }}</div>
    @endif
</div>
@endsection
