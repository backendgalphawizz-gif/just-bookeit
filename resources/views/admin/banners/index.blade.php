@extends('admin.layouts.app')
@section('title', 'Banners')
@section('page_title', 'Banners & CMS')
@section('page_subtitle', 'Promotional banners by customer, vendor, and driver apps')
@section('content')
    @php
        $tabs = [
            \App\Models\Banner::AUDIENCE_CUSTOMER => 'Customer',
            \App\Models\Banner::AUDIENCE_VENDOR => 'Vendor',
            \App\Models\Banner::AUDIENCE_DRIVER => 'Driver',
        ];
    @endphp

    <div class="jb-tabs-row">
        <div class="jb-tabs-list">
            @foreach ($tabs as $key => $label)
                <a href="{{ route('admin.banners.index', ['audience' => $key, 'search' => request('search'), 'active' => request('active'), 'from' => request('from'), 'to' => request('to')]) }}"
                   class="jb-settings-tab {{ $audience === $key ? 'jb-settings-tab--active' : '' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
        @if (auth('admin')->user()->hasPermission('banners', 'create'))
            <x-admin.button variant="primary" size="sm" :href="route('admin.banners.create', ['audience' => $audience])">+ Add Banner</x-admin.button>
        @endif
    </div>

    @push('filter_actions')
        <x-admin.export-dropdown module="banners" :params="['audience', 'search', 'active', 'from', 'to']" />
    @endpush
    <form method="GET" class="jb-filters">
        <input type="hidden" name="audience" value="{{ $audience }}">
        <div class="jb-filters-grid">
            <div class="jb-filters-field jb-filters-field--wide"><label class="jb-label">Search</label><input type="text" name="search" value="{{ request('search') }}" class="jb-input" placeholder="Banner title"></div>
            <div class="jb-filters-field"><label class="jb-label">Active</label><select name="active" class="jb-select"><option value="">All</option><option value="1" @selected(request('active') === '1')>Yes</option><option value="0" @selected(request('active') === '0')>No</option></select></div>
            @include('admin.partials.date-filter')
            @include('admin.partials.filters-end', ['resetUrl' => route('admin.banners.index', ['audience' => $audience])])
        </div>
    </form>
    <div class="jb-card">
        <div class="jb-card-header"><p class="jb-card-header-title">{{ $banners->total() }} {{ strtolower(\App\Models\Banner::audienceLabel($audience)) }} banners</p></div>
        <div class="jb-table-wrap">
            <table class="jb-table">
                <thead><tr>
                    @include('admin.partials.table-index-header')
                    <th class="w-20">Image</th>
                    <th class="jb-col-name">Title</th>
                    <th class="jb-col-date">Schedule</th>
                    <th class="text-center">Active</th>
                    <th class="jb-table-actions-col">Actions</th>
                </tr></thead>
                <tbody>
                    @forelse ($banners as $banner)
                        <tr>
                            @include('admin.partials.table-index-cell', ['paginator' => $banners])
                            <td>
                                @if ($banner->image_url)
                                    <img src="{{ $banner->image_url }}" alt="" class="h-12 w-20 rounded-lg border border-slate-200 object-cover panel-lightbox-trigger">
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="jb-col-name"><p class="font-semibold">{{ $banner->title }}</p><p class="text-xs text-slate-500">{{ $banner->subtitle }}</p></td>
                            <td class="jb-col-date text-sm text-slate-600">{{ $banner->starts_at?->format('M d') ?? '—' }} – {{ $banner->ends_at?->format('M d, Y') ?? '—' }}</td>
                            <td class="text-center">@if($banner->is_active)<span class="jb-badge bg-emerald-100 text-emerald-800">Yes</span>@else<span class="jb-badge bg-slate-100 text-slate-600">No</span>@endif</td>
                            <td class="jb-table-actions-col"><div class="jb-actions">
                                <x-admin.action-btn variant="view" :href="route('admin.banners.preview', $banner)" title="Preview banner" />
                                @if (auth('admin')->user()->hasPermission('banners', 'edit'))
                                    <x-admin.action-btn variant="edit" :href="route('admin.banners.edit', $banner)" />
                                @endif
                                @if (auth('admin')->user()->hasPermission('banners', 'delete'))
                                    <form method="POST" action="{{ route('admin.banners.destroy', $banner) }}" class="jb-action-form">@csrf @method('DELETE')
                                        <x-admin.action-btn variant="delete" type="submit" confirm="Delete this banner?" />
                                    </form>
                                @endif
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="jb-table-empty">No {{ strtolower(\App\Models\Banner::audienceLabel($audience)) }} banners yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($banners->hasPages()) {{ $banners->links() }} @endif
    </div>
@endsection
