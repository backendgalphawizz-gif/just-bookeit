@extends('vendor.layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="vp-page-head">
    <div>
        <h1 class="vp-page-title">Notifications</h1>
        <p class="vp-page-sub">
            @if ($unreadCount > 0)
                {{ $unreadCount }} unread notification{{ $unreadCount === 1 ? '' : 's' }}
            @else
                You are all caught up
            @endif
        </p>
    </div>
    @if ($unreadCount > 0)
        <form method="POST" action="{{ route('vendor.notifications.read-all') }}">
            @csrf
            <button type="submit" class="vp-btn vp-btn--outline vp-btn--sm">Mark all as read</button>
        </form>
    @endif
</div>

<div class="vp-card">
    @forelse ($notifications as $notification)
        @php
            $readState = \App\Http\Controllers\Vendor\NotificationController::readState($notification, auth('vendor')->user());
        @endphp
        <div @class(['vp-notification-row', 'is-unread' => ! $readState['is_read']])>
            <div class="vp-notification-row-body">
                <p class="vp-notification-row-title">{{ $notification->title }}</p>
                <p class="vp-notification-row-message">{{ $notification->message }}</p>
                <p class="vp-notification-row-time">{{ $notification->sent_at?->diffForHumans() ?? $notification->created_at?->diffForHumans() }}</p>
            </div>
            @unless ($readState['is_read'])
                <form method="POST" action="{{ route('vendor.notifications.read', $notification) }}">
                    @csrf
                    <button type="submit" class="vp-btn vp-btn--ghost vp-btn--sm">Mark read</button>
                </form>
            @endunless
        </div>
    @empty
        <div class="vp-empty-state">
            <p class="vp-empty-state__title">No notifications yet</p>
            <p class="vp-empty-state__text">Updates from admin and platform alerts will appear here.</p>
        </div>
    @endforelse

    @if ($notifications->hasPages())
        <div class="vp-card-pad">{{ $notifications->links('vendor.pagination.default') }}</div>
    @endif
</div>
@endsection
