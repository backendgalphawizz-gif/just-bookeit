@extends('web.layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="jbw-container">
    <div class="jbw-page-head" style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:1rem">
        <div>
            <span class="jbw-eyebrow">Inbox</span>
            <h1 class="jbw-page-title">Notifications</h1>
            <p class="jbw-page-subtitle">
                @if ($unreadCount > 0)
                    {{ $unreadCount }} unread notification{{ $unreadCount === 1 ? '' : 's' }}
                @else
                    You are all caught up
                @endif
            </p>
        </div>
        @if ($unreadCount > 0)
            <form method="POST" action="{{ route('web.notifications.read-all') }}">
                @csrf
                <button type="submit" class="jbw-btn jbw-btn--outline jbw-btn--sm">Mark all as read</button>
            </form>
        @endif
    </div>

    <div class="jbw-card" style="padding:0;overflow:hidden">
        @forelse ($notifications as $notification)
            @php
                $readState = \App\Http\Controllers\Web\NotificationController::readState($notification, auth('customer')->user());
            @endphp
            <div @class(['jbw-notification-row', 'is-unread' => ! $readState['is_read']])>
                <div class="jbw-notification-row-body">
                    <p class="jbw-notification-row-title">{{ $notification->title }}</p>
                    <p class="jbw-notification-row-message">{{ $notification->message }}</p>
                    <p class="jbw-notification-row-time">{{ $notification->sent_at?->diffForHumans() ?? $notification->created_at?->diffForHumans() }}</p>
                </div>
                @unless ($readState['is_read'])
                    <form method="POST" action="{{ route('web.notifications.read', $notification) }}">
                        @csrf
                        <button type="submit" class="jbw-btn jbw-btn--ghost jbw-btn--sm">Mark read</button>
                    </form>
                @endunless
            </div>
        @empty
            <div style="padding:2.5rem;text-align:center;color:var(--c-muted)">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 1rem;opacity:0.45"><path d="M18 8a6 6 0 10-12 0c0 7-3 7-3 7h18s-3 0-3-7"/><path d="M13.7 21a2 2 0 01-3.4 0"/></svg>
                <p style="margin:0">No notifications yet.</p>
            </div>
        @endforelse
    </div>

    @if ($notifications->hasPages())
        <div style="margin-top:1.5rem">{{ $notifications->links() }}</div>
    @endif
</div>
@endsection
