@extends('admin.layouts.app')
@section('title', 'Colors')
@section('page_title', 'Colors')
@section('page_subtitle', 'Manage color options for rental dress variants')
@section('content')
    <div class="jb-tabs-row">
        <div class="jb-tabs-list">
            <a href="{{ route('admin.sizes.index') }}" class="jb-settings-tab">Sizes</a>
            <a href="{{ route('admin.colors.index') }}" class="jb-settings-tab jb-settings-tab--active">Colors</a>
        </div>
        @if (auth('admin')->user()->hasPermission('colors', 'create'))
            <x-admin.button variant="primary" size="sm" :href="route('admin.colors.create')">+ Add Color</x-admin.button>
        @endif
    </div>

    <form method="GET" class="jb-filters">
        <div class="jb-filters-grid">
            <div class="jb-filters-field jb-filters-field--wide">
                <label class="jb-label">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="jb-input" placeholder="Color name">
            </div>
            <div class="jb-filters-field">
                <label class="jb-label">Status</label>
                <select name="status" class="jb-input">
                    <option value="">All</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
            </div>
            @include('admin.partials.filters-end', ['resetUrl' => route('admin.colors.index')])
        </div>
    </form>

    <div class="jb-card">
        <div class="jb-card-header">
            <p class="jb-card-header-title">{{ $colors->total() }} colors</p>
        </div>
        <div class="jb-table-wrap">
            <table class="jb-table">
                <thead>
                    <tr>
                        @include('admin.partials.table-index-header')
                        <th class="w-20 text-center">Order</th>
                        <th class="jb-col-name">Name</th>
                        <th>Swatch</th>
                        <th class="text-center">Active</th>
                        <th class="jb-table-actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($colors as $color)
                        <tr>
                            @include('admin.partials.table-index-cell', ['paginator' => $colors])
                            <td class="text-center">{{ $color->sort_order }}</td>
                            <td class="jb-col-name font-semibold">{{ $color->name }}</td>
                            <td>
                                <span class="inline-flex items-center gap-2 text-sm text-slate-600">
                                    <span style="display:inline-block;width:1.25rem;height:1.25rem;border-radius:999px;border:1px solid #e2e8f0;background:{{ $color->displayHex() }};"></span>
                                    {{ $color->displayHex() }}
                                </span>
                            </td>
                            <td class="text-center">{{ $color->is_active ? 'Yes' : 'No' }}</td>
                            <td class="jb-table-actions-col">
                                <div class="jb-actions">
                                    @if (auth('admin')->user()->hasPermission('colors', 'edit'))
                                        <x-admin.action-btn variant="edit" :href="route('admin.colors.edit', $color)" />
                                    @endif
                                    @if (auth('admin')->user()->hasPermission('colors', 'delete'))
                                        <form method="POST" action="{{ route('admin.colors.destroy', $color) }}" class="jb-action-form">
                                            @csrf
                                            @method('DELETE')
                                            <x-admin.action-btn variant="delete" type="submit" confirm="Delete this color?" />
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="jb-table-empty">No colors yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($colors->hasPages())
            {{ $colors->links() }}
        @endif
    </div>
@endsection
