@extends('admin.layouts.app')
@section('title', 'Sizes')
@section('page_title', 'Sizes')
@section('page_subtitle', 'Manage size options for rental dress variants')
@section('content')
    <div class="jb-tabs-row">
        <div class="jb-tabs-list">
            <a href="{{ route('admin.sizes.index') }}" class="jb-settings-tab jb-settings-tab--active">Sizes</a>
            <a href="{{ route('admin.colors.index') }}" class="jb-settings-tab">Colors</a>
        </div>
        @if (auth('admin')->user()->hasPermission('sizes', 'create'))
            <x-admin.button variant="primary" size="sm" :href="route('admin.sizes.create')">+ Add Size</x-admin.button>
        @endif
    </div>

    <form method="GET" class="jb-filters">
        <div class="jb-filters-grid">
            <div class="jb-filters-field jb-filters-field--wide">
                <label class="jb-label">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="jb-input" placeholder="Size name">
            </div>
            <div class="jb-filters-field">
                <label class="jb-label">Status</label>
                <select name="status" class="jb-input">
                    <option value="">All</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
            </div>
            @include('admin.partials.filters-end', ['resetUrl' => route('admin.sizes.index')])
        </div>
    </form>

    <div class="jb-card">
        <div class="jb-card-header">
            <p class="jb-card-header-title">{{ $sizes->total() }} sizes</p>
        </div>
        <div class="jb-table-wrap">
            <table class="jb-table">
                <thead>
                    <tr>
                        @include('admin.partials.table-index-header')
                        <th class="w-20 text-center">Order</th>
                        <th class="jb-col-name">Name</th>
                        <th class="text-center">Active</th>
                        <th class="jb-table-actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sizes as $size)
                        <tr>
                            @include('admin.partials.table-index-cell', ['paginator' => $sizes])
                            <td class="text-center">{{ $size->sort_order }}</td>
                            <td class="jb-col-name font-semibold">{{ $size->name }}</td>
                            <td class="text-center">{{ $size->is_active ? 'Yes' : 'No' }}</td>
                            <td class="jb-table-actions-col">
                                <div class="jb-actions">
                                    @if (auth('admin')->user()->hasPermission('sizes', 'edit'))
                                        <x-admin.action-btn variant="edit" :href="route('admin.sizes.edit', $size)" />
                                    @endif
                                    @if (auth('admin')->user()->hasPermission('sizes', 'delete'))
                                        <form method="POST" action="{{ route('admin.sizes.destroy', $size) }}" class="jb-action-form">
                                            @csrf
                                            @method('DELETE')
                                            <x-admin.action-btn variant="delete" type="submit" confirm="Delete this size?" />
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="jb-table-empty">No sizes yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($sizes->hasPages())
            {{ $sizes->links() }}
        @endif
    </div>
@endsection
