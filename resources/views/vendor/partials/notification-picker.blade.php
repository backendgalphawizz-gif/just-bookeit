<div class="vp-notification-picker">
    <button
        type="button"
        class="vp-icon-btn vp-notification-btn"
        @click="notificationOpen = !notificationOpen"
        :aria-expanded="notificationOpen"
        aria-label="Notifications{{ ($vendorNotificationUnread ?? 0) > 0 ? ' ('.$vendorNotificationUnread.' unread)' : '' }}"
    >
        @include('vendor.partials.nav-icon', ['icon' => 'bell'])
        @if (($vendorNotificationUnread ?? 0) > 0)
            <span class="vp-notification-badge">{{ $vendorNotificationUnread > 9 ? '9+' : $vendorNotificationUnread }}</span>
        @endif
    </button>

    <div
        class="vp-notification-panel"
        x-show="notificationOpen"
        x-cloak
        @click.outside="notificationOpen = false"
        role="dialog"
        aria-label="Notifications"
    >
        <div class="vp-notification-panel-head">
            <p class="vp-notification-panel-title">Notifications</p>
            @if (($vendorNotificationUnread ?? 0) > 0)
                <form method="POST" action="{{ route('vendor.notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="vp-notification-mark-all">Mark all read</button>
                </form>
            @endif
        </div>

        <div class="vp-notification-list">
            @forelse ($vendorNotifications ?? [] as $notification)
                @php
                    $readState = \App\Http\Controllers\Vendor\NotificationController::readState($notification, $vendorUser);
                @endphp
                <div @class(['vp-notification-item', 'is-unread' => ! $readState['is_read']])>
                    <div>
                        <p class="vp-notification-item-title">{{ $notification->title }}</p>
                        <p class="vp-notification-item-message">{{ \Illuminate\Support\Str::limit($notification->message, 90) }}</p>
                        <p class="vp-notification-item-time">{{ $notification->sent_at?->diffForHumans() }}</p>
                    </div>
                    @unless ($readState['is_read'])
                        <form method="POST" action="{{ route('vendor.notifications.read', $notification) }}">
                            @csrf
                            <button type="submit" class="vp-notification-dot" aria-label="Mark as read"></button>
                        </form>
                    @endunless
                </div>
            @empty
                <div class="vp-notification-empty">
                    <p>No notifications yet.</p>
                </div>
            @endforelse
        </div>

        <div class="vp-notification-panel-foot">
            <a href="{{ route('vendor.notifications.index') }}" class="vp-notification-view-all" @click="notificationOpen = false">View all notifications</a>
        </div>
    </div>
</div>
