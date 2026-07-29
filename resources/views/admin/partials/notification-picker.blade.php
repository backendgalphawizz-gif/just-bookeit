<div class="jb-notification-picker">
    <button
        type="button"
        class="jb-btn jb-btn-secondary jb-btn-sm jb-notification-btn"
        @click="notificationOpen = !notificationOpen"
        :aria-expanded="notificationOpen"
        aria-label="Notifications{{ ($adminInboxUnread ?? 0) > 0 ? ' ('.$adminInboxUnread.' unread)' : '' }}"
    >
        @include('admin.partials.nav-icon', ['icon' => 'bell'])
        @if (($adminInboxUnread ?? 0) > 0)
            <span class="jb-notification-badge">{{ $adminInboxUnread > 9 ? '9+' : $adminInboxUnread }}</span>
        @endif
    </button>

    <div
        class="jb-notification-panel"
        x-show="notificationOpen"
        x-cloak
        @click.outside="notificationOpen = false"
        role="dialog"
        aria-label="Notifications"
    >
        <div class="jb-notification-panel-head">
            <p class="jb-notification-panel-title">Notifications</p>
            @if (($adminInboxUnread ?? 0) > 0)
                <form method="POST" action="{{ route('admin.inbox-notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="jb-notification-mark-all">Clear all</button>
                </form>
            @endif
        </div>

        <div class="jb-notification-list">
            @forelse ($adminInboxNotifications ?? [] as $notification)
                <div class="jb-notification-item is-unread">
                    <a href="{{ route('admin.inbox-notifications.open', $notification) }}" class="jb-notification-item-link">
                        <p class="jb-notification-item-title">{{ $notification->title }}</p>
                        <p class="jb-notification-item-message">{{ \Illuminate\Support\Str::limit($notification->message, 90) }}</p>
                        <p class="jb-notification-item-time">{{ $notification->created_at?->diffForHumans() }}</p>
                    </a>
                    <form method="POST" action="{{ route('admin.inbox-notifications.read', $notification) }}">
                        @csrf
                        <button type="submit" class="jb-notification-dot" aria-label="Clear notification"></button>
                    </form>
                </div>
            @empty
                <div class="jb-notification-empty">
                    <p>No pending notifications.</p>
                </div>
            @endforelse
        </div>

        <div class="jb-notification-panel-foot">
            <a href="{{ route('admin.inbox-notifications.index') }}" class="jb-notification-view-all" @click="notificationOpen = false">View all notifications</a>
        </div>
    </div>
</div>
