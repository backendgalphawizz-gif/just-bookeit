@extends('admin.layouts.app')
@section('title', 'Categories')
@section('page_title', 'Categories')
@section('page_subtitle', 'Main categories, sub-categories, and service types used across the platform')
@section('content')
    @php
        $tabs = [
            \App\Models\Category::TYPE_MAIN => 'Categories',
            \App\Models\Category::TYPE_SUB => 'Sub-categories',
            \App\Models\Category::TYPE_SERVICE => 'Service categories',
        ];
        $showsParent = $type === \App\Models\Category::TYPE_SUB;
    @endphp

    <div class="jb-tabs-row">
        <div class="jb-tabs-list">
            @foreach ($tabs as $key => $label)
                <a href="{{ route('admin.categories.index', ['type' => $key, 'search' => request('search'), 'active' => request('active'), 'parent_id' => request('parent_id')]) }}"
                   class="jb-settings-tab {{ $type === $key ? 'jb-settings-tab--active' : '' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
        @if (auth('admin')->user()->hasPermission('categories', 'create'))
            <x-admin.button variant="primary" size="sm" :href="route('admin.categories.create', ['type' => $type])">+ Add {{ $type === \App\Models\Category::TYPE_SUB ? 'Sub-category' : 'Category' }}</x-admin.button>
        @endif
    </div>

    @push('filter_actions')
        <x-admin.export-dropdown module="categories" :params="['type', 'search', 'active', 'parent_id']" />
    @endpush
    <form method="GET" class="jb-filters">
        <input type="hidden" name="type" value="{{ $type }}">
        <div class="jb-filters-grid">
            <div class="jb-filters-field jb-filters-field--wide">
                <label class="jb-label">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="jb-input" placeholder="Name">
            </div>
            @if ($type === \App\Models\Category::TYPE_SUB)
                <div class="jb-filters-field">
                    <label class="jb-label">Parent category</label>
                    <select name="parent_id" class="jb-select">
                        <option value="">All</option>
                        @foreach ($mainCategories as $mainCategory)
                            <option value="{{ $mainCategory->id }}" @selected(request('parent_id') == $mainCategory->id)>{{ $mainCategory->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="jb-filters-field">
                <label class="jb-label">Active</label>
                <select name="active" class="jb-select">
                    <option value="">All</option>
                    <option value="1" @selected(request('active') === '1')>Yes</option>
                    <option value="0" @selected(request('active') === '0')>No</option>
                </select>
            </div>
            @include('admin.partials.filters-end', ['resetUrl' => route('admin.categories.index', ['type' => $type])])
        </div>
    </form>
    <div class="jb-card">
        <div class="jb-card-header">
            <p class="jb-card-header-title">
                {{ $categories->total() }} {{ strtolower(\App\Models\Category::typeLabel($type)) }}
            </p>
        </div>
        <div class="jb-table-wrap">
            <table class="jb-table">
                <thead>
                    <tr>
                        @include('admin.partials.table-index-header')
                        <th class="jb-col-name">Name</th>
                        @if ($showsParent)
                            <th>Parent category</th>
                        @endif
                        <th class="text-center">Sort</th>
                        <th class="text-center">Active</th>
                        <th class="jb-table-actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            @include('admin.partials.table-index-cell', ['paginator' => $categories])
                            <td class="jb-col-name font-semibold">{{ $category->name }}</td>
                            @if ($showsParent)
                                <td>{{ $category->parent?->name ?? '—' }}</td>
                            @endif
                            <td class="text-center">{{ $category->sort_order }}</td>
                            <td class="text-center">{{ $category->is_active ? 'Yes' : 'No' }}</td>
                            <td class="jb-table-actions-col">
                                <div class="jb-actions">
                                    @if (auth('admin')->user()->hasPermission('categories', 'edit'))
                                        <x-admin.action-btn variant="edit" :href="route('admin.categories.edit', $category)" />
                                    @endif
                                    @if (auth('admin')->user()->hasPermission('categories', 'delete'))
                                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="jb-action-form">
                                            @csrf
                                            @method('DELETE')
                                            <x-admin.action-btn variant="delete" type="submit" confirm="Delete this category?" />
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $showsParent ? 6 : 5 }}" class="jb-table-empty">
                                No {{ strtolower(\App\Models\Category::typeLabel($type)) }} yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($categories->hasPages())
            {{ $categories->links() }}
        @endif
    </div>
@endsection
