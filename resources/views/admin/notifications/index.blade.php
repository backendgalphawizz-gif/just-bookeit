@extends('admin.layouts.app')
@section('title', 'Notifications')
@section('page_title', 'Notifications')
@section('page_subtitle', 'Broadcast push, email, or SMS to users and vendors')
@section('content')
    @push('filter_actions')
        <x-admin.export-dropdown module="notifications" :params="['from', 'to']" />
        @if (auth('admin')->user()->hasPermission('notifications', 'create'))
            <x-admin.button variant="primary" size="sm" :href="route('admin.notifications.create')">+ Send notification</x-admin.button>
        @endif
    @endpush
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6">
        <div class="jb-stat-card"><p class="jb-stat-label">Total sent</p><p class="jb-stat-value">{{ $stats['total_sent'] }}</p></div>
        <div class="jb-stat-card"><p class="jb-stat-label">This month</p><p class="jb-stat-value">{{ $stats['this_month'] }}</p></div>
        <div class="jb-stat-card"><p class="jb-stat-label">Active customers</p><p class="jb-stat-value">{{ $stats['customers'] }}</p></div>
        <div class="jb-stat-card"><p class="jb-stat-label">Active vendors</p><p class="jb-stat-value">{{ $stats['vendors'] }}</p></div>
    </div>

    <form method="GET" class="jb-filters">
        <div class="jb-filters-grid">
            @include('admin.partials.date-filter')
            @include('admin.partials.filters-end', ['resetUrl' => route('admin.notifications.index')])
        </div>
    </form>

    <div class="jb-card">
        <div class="jb-card-header"><p class="jb-card-header-title">Notification history</p></div>
        <div class="jb-table-wrap">
            <table class="jb-table">
                <thead><tr>
                    @include('admin.partials.table-index-header')
                    <th class="jb-col-name">Title</th>
                    <th>Channel</th>
                    <th>Audience</th>
                    <th class="text-center">Recipients</th>
                    <th class="jb-col-status">Status</th>
                    <th class="jb-col-date">Sent</th>
                    <th class="jb-table-actions-col">Actions</th>
                </tr></thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            @include('admin.partials.table-index-cell', ['paginator' => $logs])
                            <td class="jb-col-name font-semibold">{{ $log->title }}</td>
                            <td>{{ strtoupper($log->channel) }}</td>
                            <td>{{ str_replace('_', ' ', $log->audience) }}</td>
                            <td class="text-center">{{ $log->recipients_count }}</td>
                            <td class="jb-col-status"><span class="jb-badge bg-emerald-100 text-emerald-800">{{ $log->status }}</span></td>
                            <td class="jb-col-date text-sm text-slate-500">{{ $log->sent_at?->format('M d, Y h:i A') ?? '—' }}</td>
                            <td class="jb-table-actions-col"><div class="jb-actions"><x-admin.action-btn variant="view" :href="route('admin.notifications.show', $log)" /></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="jb-table-empty">No notifications sent yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages()) {{ $logs->links() }} @endif
    </div>
@endsection
