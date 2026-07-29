@extends('admin.layouts.app')

@section('title', 'Notifications')
@section('page_title', 'Notifications')
@section('page_subtitle', 'Product approvals, support, vendors, refunds, and more')
@section('back_href', route('admin.dashboard'))

@section('content')
    <div class="jb-card">
        <div class="jb-card-header jb-card-header--stack">
            <div>
                <p class="jb-card-header-title">{{ $unreadCount }} unread notification{{ $unreadCount === 1 ? '' : 's' }}</p>
                <p class="text-sm text-slate-500">Opening a notification clears it automatically.</p>
            </div>
            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('admin.inbox-notifications.read-all') }}">
                    @csrf
                    <x-admin.button variant="secondary" size="sm" type="submit">Clear all</x-admin.button>
                </form>
            @endif
        </div>
        <div class="jb-card-body divide-y divide-slate-100 p-0">
            @forelse ($notifications as $notification)
                <div class="jb-notification-row is-unread">
                    <div class="jb-notification-row-body">
                        <p class="jb-notification-row-title">{{ $notification->title }}</p>
                        <p class="jb-notification-row-message">{{ $notification->message }}</p>
                        <p class="jb-notification-row-time">{{ $notification->created_at?->diffForHumans() }}</p>
                    </div>
                    <div class="jb-notification-row-actions">
                        <x-admin.button variant="primary" size="sm" :href="route('admin.inbox-notifications.open', $notification)">Review</x-admin.button>
                        <form method="POST" action="{{ route('admin.inbox-notifications.read', $notification) }}">
                            @csrf
                            <x-admin.button variant="secondary" size="sm" type="submit">Clear</x-admin.button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="jb-notification-empty p-8">
                    <p>No pending notifications right now.</p>
                </div>
            @endforelse
        </div>
        @if ($notifications->hasPages())
            <div class="jb-card-body border-t border-slate-100 pt-0">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
@endsection
