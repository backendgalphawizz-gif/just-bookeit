@extends('admin.layouts.app')
@section('title', 'Contact messages')
@section('page_title', 'Contact messages')
@section('page_subtitle', 'Inquiries submitted from the Contact Us form')
@section('content')
    <div class="grid gap-4 sm:grid-cols-3 mb-6">
        <div class="jb-stat-card"><p class="jb-stat-label">Total</p><p class="jb-stat-value">{{ $stats['total'] }}</p></div>
        <div class="jb-stat-card"><p class="jb-stat-label">Unread</p><p class="jb-stat-value">{{ $stats['unread'] }}</p></div>
        <div class="jb-stat-card"><p class="jb-stat-label">Read</p><p class="jb-stat-value">{{ $stats['read'] }}</p></div>
    </div>

    <form method="GET" class="jb-filters">
        <div class="jb-filters-grid">
            <div class="jb-filters-field jb-filters-field--wide">
                <label class="jb-label">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="jb-input" placeholder="Email, subject, or message">
            </div>
            <div class="jb-filters-field">
                <label class="jb-label">Status</label>
                <select name="status" class="jb-select">
                    <option value="">All</option>
                    <option value="unread" @selected(request('status') === 'unread')>Unread</option>
                    <option value="read" @selected(request('status') === 'read')>Read</option>
                </select>
            </div>
            <div class="jb-filters-field">
                <label class="jb-label">Type</label>
                <select name="inquiry_type" class="jb-select">
                    <option value="">All</option>
                    @foreach (\App\Models\ContactMessage::INQUIRY_TYPES as $value => $label)
                        <option value="{{ $value }}" @selected(request('inquiry_type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @include('admin.partials.date-filter')
            @include('admin.partials.filters-end', ['resetUrl' => route('admin.contact-messages.index')])
        </div>
    </form>

    <div class="jb-card">
        <div class="jb-card-header">
            <p class="jb-card-header-title">{{ $messages->total() }} messages</p>
        </div>
        <div class="jb-table-wrap">
            <table class="jb-table">
                <thead>
                    <tr>
                        @include('admin.partials.table-index-header')
                        <th class="jb-col-name">Subject</th>
                        <th>Email</th>
                        <th>Type</th>
                        <th class="jb-col-status">Status</th>
                        <th class="jb-col-date">Received</th>
                        <th class="jb-table-actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($messages as $item)
                        <tr class="{{ $item->isUnread() ? 'font-semibold' : '' }}">
                            @include('admin.partials.table-index-cell', ['paginator' => $messages])
                            <td class="jb-col-name">
                                <a href="{{ route('admin.contact-messages.show', $item) }}" class="hover:underline">
                                    {{ \Illuminate\Support\Str::limit($item->subject, 60) }}
                                </a>
                            </td>
                            <td>{{ $item->email }}</td>
                            <td>{{ $item->inquiryTypeLabel() }}</td>
                            <td class="jb-col-status">
                                @if ($item->isUnread())
                                    <span class="jb-badge bg-amber-100 text-amber-800">Unread</span>
                                @else
                                    <span class="jb-badge bg-emerald-100 text-emerald-800">Read</span>
                                @endif
                            </td>
                            <td class="jb-col-date text-sm text-slate-500">{{ $item->created_at?->format('M d, Y h:i A') }}</td>
                            <td class="jb-table-actions-col">
                                <div class="jb-actions">
                                    <x-admin.action-btn variant="view" :href="route('admin.contact-messages.show', $item)" />
                                    @if (auth('admin')->user()->hasPermission('contact_messages', 'delete'))
                                        <form method="POST" action="{{ route('admin.contact-messages.destroy', $item) }}" class="jb-action-form">
                                            @csrf
                                            @method('DELETE')
                                            <x-admin.action-btn variant="delete" type="submit" confirm="Delete this contact message?" />
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="jb-table-empty">No contact messages yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($messages->hasPages())
            {{ $messages->links() }}
        @endif
    </div>
@endsection
