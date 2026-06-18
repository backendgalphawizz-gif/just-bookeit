@extends('admin.layouts.app')
@section('title', 'Categories')
@section('page_title', 'Categories')
@section('page_subtitle', 'Manage catalog categories, sub-categories, and service types used across the platform')
@section('content')
    @php
        $isCatalog = $type === 'catalog';
        $isService = $type === \App\Models\Category::TYPE_SERVICE;
        $subcategoryTotal = $subcategoryTotal ?? 0;
        $activeFilter = request()->has('active') ? (string) request('active') : '';
        $activeTabs = [
            '' => 'All',
            '1' => 'Active',
            '0' => 'Inactive',
        ];
        $filterParams = array_filter([
            'type' => $type,
            'search' => request('search'),
            'active' => $activeFilter !== '' ? $activeFilter : null,
            'parent_id' => request('parent_id'),
        ], fn ($value) => $value !== null && $value !== '');
    @endphp

    <div class="jb-tabs-row">
        <div class="jb-tabs-list">
            <a href="{{ route('admin.categories.index', array_merge($filterParams, ['type' => 'catalog', 'parent_id' => request('parent_id')])) }}"
               class="jb-settings-tab {{ $isCatalog ? 'jb-settings-tab--active' : '' }}">
                Categories & sub-categories
            </a>
            <a href="{{ route('admin.categories.index', ['type' => \App\Models\Category::TYPE_SERVICE, 'search' => request('search'), 'active' => request('active')]) }}"
               class="jb-settings-tab {{ $isService ? 'jb-settings-tab--active' : '' }}">
                Service categories
            </a>
        </div>
        @if (auth('admin')->user()->hasPermission('categories', 'create'))
            <div class="flex flex-wrap items-center gap-2">
                @if ($isCatalog)
                    <x-admin.button variant="primary" size="sm" :href="route('admin.categories.create', ['type' => \App\Models\Category::TYPE_MAIN])">+ Add category</x-admin.button>
                    <x-admin.button variant="secondary" size="sm" :href="route('admin.categories.create', ['type' => \App\Models\Category::TYPE_SUB])">+ Add sub-category</x-admin.button>
                @else
                    <x-admin.button variant="primary" size="sm" :href="route('admin.categories.create', ['type' => \App\Models\Category::TYPE_SERVICE])">+ Add service category</x-admin.button>
                @endif
            </div>
        @endif
    </div>

    @push('filter_actions')
        <x-admin.export-dropdown module="categories" :params="['type', 'search', 'active', 'parent_id']" />
    @endpush
    <form method="GET" class="jb-filters">
        <input type="hidden" name="type" value="{{ $type }}">
        @if ($activeFilter !== '')
            <input type="hidden" name="active" value="{{ $activeFilter }}">
        @endif
        <div class="jb-filters-grid">
            <div class="jb-filters-field jb-filters-field--wide">
                <label class="jb-label">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="jb-input" placeholder="Name">
            </div>
            @if ($isCatalog)
                <div class="jb-filters-field">
                    <label class="jb-label">Category</label>
                    <select name="parent_id" class="jb-select">
                        <option value="">All categories</option>
                        @foreach ($mainCategories as $mainCategory)
                            <option value="{{ $mainCategory->id }}" @selected(request('parent_id') == $mainCategory->id)>{{ $mainCategory->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @include('admin.partials.filters-end', ['resetUrl' => route('admin.categories.index', ['type' => $type])])
        </div>
    </form>

    <div class="jb-tabs-row jb-tabs-row--nested">
        <div class="jb-tabs-list">
            @foreach ($activeTabs as $key => $tabLabel)
                @php
                    $tabParams = array_merge(
                        request()->except('page', 'active'),
                        $key !== '' ? ['active' => $key] : []
                    );
                @endphp
                <a href="{{ route('admin.categories.index', $tabParams) }}"
                   class="jb-settings-tab {{ $activeFilter === (string) $key ? 'jb-settings-tab--active' : '' }}">
                    {{ $tabLabel }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="jb-card" @if ($isCatalog) x-data="jbCategoryTree(@js($categories->pluck('id')->values()))" @endif>
        @unless ($isCatalog)
            <div class="jb-card-header">
                <p class="jb-card-header-title">
                    {{ $categories->total() }} {{ strtolower(\App\Models\Category::typeLabel($type)) }}
                </p>
            </div>
        @endunless
        <div class="jb-table-wrap">
            <table class="jb-table">
                <thead>
                    <tr>
                        @include('admin.partials.table-index-header')
                        @if ($isCatalog)
                            <th class="jb-col-status">Type</th>
                        @endif
                        <th class="jb-col-image">Image</th>
                        <th class="jb-col-name">Name</th>
                        @if ($isCatalog)
                            <th>Under category</th>
                        @endif
                        <th class="text-center">Sort</th>
                        <th class="jb-col-status">Status</th>
                        <th class="jb-table-actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($isCatalog)
                        @forelse ($categories as $category)
                            <tr class="jb-category-row jb-category-row--parent">
                                @include('admin.partials.table-index-cell', ['paginator' => $categories])
                                <td class="jb-col-status">
                                    <span class="jb-category-type jb-category-type--main">Category</span>
                                </td>
                                @include('admin.categories._image-cell', ['category' => $category])
                                <td class="jb-col-name font-semibold text-slate-900">
                                    <div class="jb-category-name-cell">
                                        @if ($category->subcategories->isNotEmpty())
                                            <button
                                                type="button"
                                                class="jb-category-toggle"
                                                @click="toggle({{ $category->id }})"
                                                :aria-expanded="isOpen({{ $category->id }})"
                                                aria-label="Toggle sub-categories for {{ $category->name }}"
                                            >
                                                <svg class="jb-category-toggle__icon" :class="{ 'jb-category-toggle__icon--open': isOpen({{ $category->id }}) }" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <polyline points="9 18 15 12 9 6"></polyline>
                                                </svg>
                                            </button>
                                        @else
                                            <span class="jb-category-toggle jb-category-toggle--placeholder" aria-hidden="true"></span>
                                        @endif
                                        <span>{{ $category->name }}</span>
                                        @if ($category->subcategories->isNotEmpty())
                                            <span class="jb-category-subcount">{{ $category->subcategories->count() }} {{ Str::plural('sub-category', $category->subcategories->count()) }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-slate-400">—</td>
                                <td class="text-center">{{ $category->sort_order }}</td>
                                <td class="jb-col-status">
                                    @include('admin.components.status-badge', ['status' => $category->is_active ? 'active' : 'inactive'])
                                </td>
                                <td class="jb-table-actions-col">
                                    <div class="jb-actions">
                                        @if (auth('admin')->user()->hasPermission('categories', 'edit'))
                                            <x-admin.action-btn variant="edit" :href="route('admin.categories.edit', $category)" />
                                        @endif
                                        @if (auth('admin')->user()->hasPermission('categories', 'delete'))
                                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="jb-action-form">
                                                @csrf
                                                @method('DELETE')
                                                <x-admin.action-btn variant="delete" type="submit" confirm="Delete this category? Remove its sub-categories first." />
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @forelse ($category->subcategories as $subcategory)
                                <tr class="jb-category-row jb-category-row--child" x-show="isOpen({{ $category->id }})" x-cloak>
                                    <td class="jb-col-sn text-slate-300">—</td>
                                    <td class="jb-col-status">
                                        <span class="jb-category-type jb-category-type--sub">Sub-category</span>
                                    </td>
                                    @include('admin.categories._image-cell', ['category' => $subcategory])
                                    <td class="jb-col-name">
                                        <span class="jb-category-child-name">{{ $subcategory->name }}</span>
                                    </td>
                                    <td>{{ $category->name }}</td>
                                    <td class="text-center">{{ $subcategory->sort_order }}</td>
                                    <td class="jb-col-status">
                                        @include('admin.components.status-badge', ['status' => $subcategory->is_active ? 'active' : 'inactive'])
                                    </td>
                                    <td class="jb-table-actions-col">
                                        <div class="jb-actions">
                                            @if (auth('admin')->user()->hasPermission('categories', 'edit'))
                                                <x-admin.action-btn variant="edit" :href="route('admin.categories.edit', $subcategory)" />
                                            @endif
                                            @if (auth('admin')->user()->hasPermission('categories', 'delete'))
                                                <form method="POST" action="{{ route('admin.categories.destroy', $subcategory) }}" class="jb-action-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-admin.action-btn variant="delete" type="submit" confirm="Delete this sub-category?" />
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr class="jb-category-row jb-category-row--empty" x-show="isOpen({{ $category->id }})" x-cloak>
                                    <td class="jb-col-sn text-slate-300">—</td>
                                    <td class="jb-col-status">
                                        <span class="jb-category-type jb-category-type--sub">Sub-category</span>
                                    </td>
                                    <td colspan="6" class="text-sm text-slate-400">No sub-categories yet.</td>
                                </tr>
                            @endforelse
                        @empty
                            <tr>
                                <td colspan="8" class="jb-table-empty">No categories yet.</td>
                            </tr>
                        @endforelse
                    @else
                        @forelse ($categories as $category)
                            <tr>
                                @include('admin.partials.table-index-cell', ['paginator' => $categories])
                                @include('admin.categories._image-cell', ['category' => $category])
                                <td class="jb-col-name font-semibold">{{ $category->name }}</td>
                                <td class="text-center">{{ $category->sort_order }}</td>
                                <td class="jb-col-status">
                                    @include('admin.components.status-badge', ['status' => $category->is_active ? 'active' : 'inactive'])
                                </td>
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
                                <td colspan="6" class="jb-table-empty">No service categories yet.</td>
                            </tr>
                        @endforelse
                    @endif
                </tbody>
            </table>
        </div>
        @if ($categories->hasPages())
            {{ $categories->links() }}
        @endif
    </div>
@endsection

@push('styles')
<style>
    .jb-category-row--parent td {
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
    }

    .jb-category-row--child td,
    .jb-category-row--empty td {
        background: #fff;
    }

    .jb-category-row--child .jb-category-child-name,
    .jb-category-row--empty td {
        padding-left: 1.25rem;
    }

    .jb-category-type {
        display: inline-flex;
        align-items: center;
        border-radius: 9999px;
        padding: 0.15rem 0.55rem;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .jb-category-type--main {
        background: #ede9fe;
        color: #6d28d9;
    }

    .jb-category-type--sub {
        background: #e0f2fe;
        color: #0369a1;
    }

    .jb-category-name-cell {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        min-width: 0;
    }

    .jb-category-toggle {
        display: inline-flex;
        flex-shrink: 0;
        align-items: center;
        justify-content: center;
        width: 1.5rem;
        height: 1.5rem;
        border: 0;
        border-radius: 0.375rem;
        background: transparent;
        color: #64748b;
        cursor: pointer;
        transition: background-color 0.15s ease, color 0.15s ease;
    }

    .jb-category-toggle:hover {
        background: #e2e8f0;
        color: #0f172a;
    }

    .jb-category-toggle--placeholder {
        pointer-events: none;
    }

    .jb-category-toggle__icon {
        transition: transform 0.15s ease;
    }

    .jb-category-toggle__icon--open {
        transform: rotate(90deg);
    }

    .jb-category-subcount {
        flex-shrink: 0;
        border-radius: 9999px;
        background: #e2e8f0;
        padding: 0.1rem 0.45rem;
        font-size: 0.68rem;
        font-weight: 600;
        color: #475569;
        white-space: nowrap;
    }

    .jb-col-image {
        width: 4.5rem;
    }

    .jb-category-table-img {
        display: block;
        width: 3rem;
        height: 3rem;
        border-radius: 0.5rem;
        border: 1px solid #e2e8f0;
        object-fit: cover;
        background: #fff;
    }

    .jb-category-table-img--empty {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #94a3b8;
        background: #f8fafc;
    }
</style>
@endpush
