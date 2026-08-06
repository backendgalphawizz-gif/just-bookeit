@php
    $initialNotifications = collect($adminInboxNotifications ?? [])->map(function ($notification) {
        return [
            'id' => $notification->id,
            'title' => $notification->title,
            'message' => \Illuminate\Support\Str::limit($notification->message, 90),
            'time' => \App\Support\AdminDateTime::format($notification->created_at, 'M d · h:i A'),
            'open_url' => route('admin.inbox-notifications.open', $notification),
            'read_url' => route('admin.inbox-notifications.read', $notification),
        ];
    })->values();
@endphp

<div
    class="jb-notification-picker"
    x-data="adminInboxNotifications({
        unread: {{ (int) ($adminInboxUnread ?? 0) }},
        items: {{ \Illuminate\Support\Js::from($initialNotifications) }},
        feedUrl: {{ \Illuminate\Support\Js::from(route('admin.inbox-notifications.feed')) }},
        readAllUrl: {{ \Illuminate\Support\Js::from(route('admin.inbox-notifications.read-all')) }},
        pollMs: 3000,
    })"
    x-init="startPolling()"
>
    <button
        type="button"
        class="jb-btn jb-btn-secondary jb-btn-sm jb-notification-btn"
        @click="notificationOpen = !notificationOpen"
        :aria-expanded="notificationOpen"
        :aria-label="unread > 0 ? `Notifications (${unread} unread)` : 'Notifications'"
    >
        @include('admin.partials.nav-icon', ['icon' => 'bell'])
        <span
            class="jb-notification-badge"
            x-show="unread > 0"
            x-text="unread > 9 ? '9+' : unread"
            x-cloak
        ></span>
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
            <button
                type="button"
                class="jb-notification-mark-all"
                x-show="unread > 0"
                x-cloak
                @click="clearAll()"
                :disabled="busy"
            >Clear all</button>
        </div>

        <div class="jb-notification-list">
            <template x-if="items.length === 0">
                <div class="jb-notification-empty">
                    <p>No pending notifications.</p>
                </div>
            </template>
            <template x-for="item in items" :key="item.id">
                <div class="jb-notification-item is-unread">
                    <a :href="item.open_url" class="jb-notification-item-link">
                        <p class="jb-notification-item-title" x-text="item.title"></p>
                        <p class="jb-notification-item-message" x-text="item.message"></p>
                        <p class="jb-notification-item-time" x-text="item.time"></p>
                    </a>
                    <button
                        type="button"
                        class="jb-notification-dot"
                        aria-label="Clear notification"
                        @click.prevent="clearOne(item)"
                        :disabled="busy"
                    ></button>
                </div>
            </template>
        </div>

        <div class="jb-notification-panel-foot">
            <a href="{{ route('admin.inbox-notifications.index') }}" class="jb-notification-view-all" @click="notificationOpen = false">View all notifications</a>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
function adminInboxNotifications(config) {
    return {
        notificationOpen: false,
        unread: Number(config.unread || 0),
        items: Array.isArray(config.items) ? config.items : [],
        feedUrl: config.feedUrl,
        readAllUrl: config.readAllUrl,
        pollMs: Number(config.pollMs || 3000),
        busy: false,
        timer: null,

        csrfToken() {
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta?.content) return meta.content;
            const input = document.querySelector('input[name="_token"]');
            return input?.value || '';
        },

        startPolling() {
            this.refresh();
            this.timer = window.setInterval(() => {
                if (document.hidden) return;
                this.refresh();
            }, this.pollMs);

            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) this.refresh();
            });
        },

        async refresh() {
            try {
                const res = await fetch(this.feedUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                if (!res.ok) return;
                const data = await res.json();
                this.unread = Number(data.unread_count || 0);
                this.items = Array.isArray(data.notifications) ? data.notifications : [];
            } catch (e) {
                // Keep last known state on network blips.
            }
        },

        async clearOne(item) {
            if (this.busy || !item?.read_url) return;
            this.busy = true;
            try {
                const res = await fetch(item.read_url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': this.csrfToken(),
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({}),
                });
                if (res.ok) {
                    this.items = this.items.filter((n) => n.id !== item.id);
                    this.unread = Math.max(0, this.unread - 1);
                    await this.refresh();
                }
            } finally {
                this.busy = false;
            }
        },

        async clearAll() {
            if (this.busy || this.unread < 1) return;
            this.busy = true;
            try {
                const res = await fetch(this.readAllUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': this.csrfToken(),
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({}),
                });
                if (res.ok) {
                    this.items = [];
                    this.unread = 0;
                }
            } finally {
                this.busy = false;
            }
        },
    };
}
</script>
@endpush
@endonce
