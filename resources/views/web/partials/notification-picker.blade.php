<div class="jbw-notification-picker">
    @auth('customer')
        @unless ($webCustomer->is_guest)
            <button
                type="button"
                class="jbw-icon-btn jbw-notification-btn"
                @click="notificationOpen = !notificationOpen; locationOpen = false"
                :aria-expanded="notificationOpen"
                aria-label="Notifications{{ ($webNotificationUnread ?? 0) > 0 ? ' ('.$webNotificationUnread.' unread)' : '' }}"
            >
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 8a6 6 0 10-12 0c0 7-3 7-3 7h18s-3 0-3-7"/><path d="M13.7 21a2 2 0 01-3.4 0"/></svg>
                @if (($webNotificationUnread ?? 0) > 0)
                    <span class="jbw-notification-badge">{{ $webNotificationUnread > 9 ? '9+' : $webNotificationUnread }}</span>
                @endif
            </button>

            <div
                class="jbw-notification-panel"
                x-show="notificationOpen"
                x-cloak
                @click.outside="notificationOpen = false"
                role="dialog"
                aria-label="Notifications"
            >
                <div class="jbw-notification-panel-head">
                    <p class="jbw-notification-panel-title">Notifications</p>
                    @if (($webNotificationUnread ?? 0) > 0)
                        <form method="POST" action="{{ route('web.notifications.read-all') }}">
                            @csrf
                            <button type="submit" class="jbw-notification-mark-all">Mark all read</button>
                        </form>
                    @endif
                </div>

                <div class="jbw-notification-list">
                    @forelse ($webNotifications ?? [] as $notification)
                        @php
                            $readState = \App\Http\Controllers\Web\NotificationController::readState($notification, $webCustomer);
                        @endphp
                        <div @class(['jbw-notification-item', 'is-unread' => ! $readState['is_read']])>
                            <div>
                                <p class="jbw-notification-item-title">{{ $notification->title }}</p>
                                <p class="jbw-notification-item-message">{{ \Illuminate\Support\Str::limit($notification->message, 90) }}</p>
                                <p class="jbw-notification-item-time">{{ $notification->sent_at?->diffForHumans() }}</p>
                            </div>
                            @unless ($readState['is_read'])
                                <form method="POST" action="{{ route('web.notifications.read', $notification) }}">
                                    @csrf
                                    <button type="submit" class="jbw-notification-dot" aria-label="Mark as read"></button>
                                </form>
                            @endunless
                        </div>
                    @empty
                        <div class="jbw-notification-empty">
                            <p>No notifications yet.</p>
                        </div>
                    @endforelse
                </div>

                <div class="jbw-notification-panel-foot">
                    <a href="{{ route('web.notifications.index') }}" class="jbw-notification-view-all">View all notifications</a>
                </div>
            </div>
        @else
            <a href="{{ route('web.register', ['redirect' => route('web.notifications.index')]) }}" class="jbw-icon-btn jbw-notification-btn" aria-label="Sign in for notifications">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 10-12 0c0 7-3 7-3 7h18s-3 0-3-7"/><path d="M13.7 21a2 2 0 01-3.4 0"/></svg>
            </a>
        @endunless
    @else
        <a href="{{ route('web.login', ['redirect' => route('web.notifications.index')]) }}" class="jbw-icon-btn jbw-notification-btn" aria-label="Sign in for notifications">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 10-12 0c0 7-3 7-3 7h18s-3 0-3-7"/><path d="M13.7 21a2 2 0 01-3.4 0"/></svg>
        </a>
    @endauth
</div>
