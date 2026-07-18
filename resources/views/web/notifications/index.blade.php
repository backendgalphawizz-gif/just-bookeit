@extends('web.layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="jbw-container jbw-page-shell">
    <div class="jbw-notif-page">
        <header class="jbw-notif-hero">
            <div class="jbw-notif-hero-copy">
                <p class="jbw-eyebrow">Inbox</p>
                <h1 class="jbw-page-title">Notifications</h1>
                <p class="jbw-page-subtitle">
                    @if ($unreadCount > 0)
                        {{ $unreadCount }} unread {{ $unreadCount === 1 ? 'message' : 'messages' }} waiting for you
                    @else
                        You’re all caught up — no unread messages
                    @endif
                </p>
            </div>

            <div class="jbw-notif-hero-actions">
                @if ($unreadCount > 0)
                    <span class="jbw-notif-count-pill">{{ $unreadCount }} unread</span>
                    <form method="POST" action="{{ route('web.notifications.read-all') }}">
                        @csrf
                        <button type="submit" class="jbw-btn jbw-btn--outline jbw-btn--sm">Mark all as read</button>
                    </form>
                @endif
            </div>
        </header>

        <div class="jbw-notif-panel">
            @forelse ($notifications as $notification)
                @php
                    $readState = \App\Http\Controllers\Web\NotificationController::readState($notification, auth('customer')->user());
                    $isUnread = ! $readState['is_read'];
                    $when = $notification->sent_at?->diffForHumans() ?? $notification->created_at?->diffForHumans();
                @endphp
                <article @class(['jbw-notif-item', 'is-unread' => $isUnread])>
                    <div class="jbw-notif-item-icon" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                            <path d="M10 21a2 2 0 0 0 4 0"/>
                        </svg>
                    </div>

                    <div class="jbw-notif-item-body">
                        <div class="jbw-notif-item-top">
                            <h2 class="jbw-notif-item-title">
                                @if ($isUnread)
                                    <span class="jbw-notif-unread-dot" aria-hidden="true"></span>
                                @endif
                                {{ $notification->title }}
                            </h2>
                            <time class="jbw-notif-item-time" datetime="{{ ($notification->sent_at ?? $notification->created_at)?->toIso8601String() }}">
                                {{ $when }}
                            </time>
                        </div>
                        <p class="jbw-notif-item-message">{{ $notification->message }}</p>
                        @if ($isUnread)
                            <form method="POST" action="{{ route('web.notifications.read', $notification) }}" class="jbw-notif-item-action">
                                @csrf
                                <button type="submit" class="jbw-notif-mark-btn">Mark as read</button>
                            </form>
                        @else
                            <p class="jbw-notif-item-status">Read</p>
                        @endif
                    </div>
                </article>
            @empty
                <div class="jbw-notif-empty">
                    <div class="jbw-notif-empty-icon" aria-hidden="true">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                            <path d="M10 21a2 2 0 0 0 4 0"/>
                        </svg>
                    </div>
                    <h2 class="jbw-notif-empty-title">No notifications yet</h2>
                    <p class="jbw-notif-empty-text">Order updates, booking alerts, and important messages from Just Book IT will show up here.</p>
                    <a href="{{ route('web.catalog.index') }}" class="jbw-btn jbw-btn--primary jbw-btn--sm">Browse catalog</a>
                </div>
            @endforelse
        </div>

        @if ($notifications->hasPages())
            <div class="jbw-notif-pagination">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
