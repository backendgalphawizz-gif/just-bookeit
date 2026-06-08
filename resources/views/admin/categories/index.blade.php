@extends('admin.layouts.app')
@section('title', 'Categories')
@section('page_title', 'Categories')
@section('page_subtitle', 'Main and service categories')
@section('content')
    @push('filter_actions')
        <x-admin.export-dropdown module="categories" :params="['search', 'type', 'from', 'to']" />
        @if (auth('admin')->user()->hasPermission('categories', 'create'))
            <x-admin.button variant="primary" size="sm" :href="route('admin.categories.create')">+ Add Category</x-admin.button>
        @endif
    @endpush
    <form method="GET" class="jb-filters">
        <div class="jb-filters-grid">
            <div class="jb-filters-field jb-filters-field--wide"><label class="jb-label">Search</label><input type="text" name="search" value="{{ request('search') }}" class="jb-input"></div>
            <div class="jb-filters-field"><label class="jb-label">Type</label>
                <select name="type" class="jb-select"><option value="">All</option><option value="main" @selected(request('type') === 'main')>Main</option><option value="service" @selected(request('type') === 'service')>Service</option></select>
            </div>
            @include('admin.partials.date-filter')
            @include('admin.partials.filters-end', ['resetUrl' => route('admin.categories.index')])
        </div>
    </form>
    <div class="jb-card">
        <div class="jb-card-header"><p class="jb-card-header-title">{{ $categories->total() }} categories</p></div>
        <div class="jb-table-wrap">
            <table class="jb-table">
                <thead><tr>
                    @include('admin.partials.table-index-header')
                    <th class="jb-col-name">Name</th>
                    <th>Type</th>
                    <th>Parent</th>
                    <th class="text-center">Active</th>
                    <th class="jb-table-actions-col">Actions</th>
                </tr></thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            @include('admin.partials.table-index-cell', ['paginator' => $categories])
                            <td class="jb-col-name font-semibold">{{ $category->name }}</td>
                            <td class="capitalize">{{ $category->type }}</td>
                            <td>{{ $category->parent?->name ?? '—' }}</td>
                            <td class="text-center">{{ $category->is_active ? 'Yes' : 'No' }}</td>
                            <td class="jb-table-actions-col"><div class="jb-actions">
                                @if (auth('admin')->user()->hasPermission('categories', 'edit'))
                                    <x-admin.action-btn variant="edit" :href="route('admin.categories.edit', $category)" />
                                @endif
                                @if (auth('admin')->user()->hasPermission('categories', 'delete'))
                                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="jb-action-form">@csrf @method('DELETE')
                                        <x-admin.action-btn variant="delete" type="submit" confirm="Delete this category?" />
                                    </form>
                                @endif
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="jb-table-empty">No categories.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($categories->hasPages()) {{ $categories->links() }} @endif
    </div>
@endsection
