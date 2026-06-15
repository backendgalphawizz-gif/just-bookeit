@extends('admin.layouts.app')

@section('title', 'Customers')
@section('page_title', 'Customers')
@section('page_subtitle', 'Manage registered users and accounts')

@section('content')
    @push('filter_actions')
        <x-admin.export-dropdown module="customers" :params="['search', 'status', 'city', 'from', 'to', 'registered_on']" />
        @if (auth('admin')->user()->hasPermission('customers', 'create'))
            <x-admin.button variant="primary" size="sm" :href="route('admin.customers.create')">+ Add Customer</x-admin.button>
        @endif
    @endpush
    <form method="GET" class="jb-filters">
        <div class="jb-filters-grid">
            <div class="jb-filters-field jb-filters-field--wide">
                <label class="jb-label">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, email ID, mobile no, ID..." class="jb-input">
            </div>
            <div class="jb-filters-field">
                <label class="jb-label">Status</label>
                <select name="status" class="jb-select">
                    <option value="">All statuses</option>
                    @foreach (['active', 'suspended', 'blocked'] as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="jb-filters-field">
                <label class="jb-label">City</label>
                <input type="text" name="city" value="{{ request('city') }}" placeholder="City" class="jb-input">
            </div>
            @include('admin.partials.date-filter')
            @include('admin.partials.filter-date-input', [
                'name' => 'registered_on',
                'id' => 'filter-registered',
                'label' => 'Registered on',
            ])
            @include('admin.partials.filters-end', ['resetUrl' => route('admin.customers.index')])
        </div>
    </form>

    <div class="jb-card">
        <div class="jb-card-header">
            <p class="jb-card-header-title">{{ $customers->total() }} customers</p>
        </div>
        <div class="jb-table-wrap">
            <table class="jb-table">
                <thead>
                    <tr>
                        @include('admin.partials.table-index-header')
                        <th class="jb-col-id">Customer ID</th>
                        <th class="jb-col-name">Name</th>
                        <th>Mobile No</th>
                        <th>City</th>
                        <th class="jb-col-date">Registered</th>
                        <th class="text-center">Orders</th>
                        <th class="jb-col-status">Status</th>
                        <th class="jb-table-actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                            @include('admin.partials.table-index-cell', ['paginator' => $customers])
                            <td class="jb-col-id"><span class="font-mono text-xs font-semibold text-slate-500">{{ $customer->customer_code }}</span></td>
                            <td class="jb-col-name">
                                <div class="jb-actor-cell">
                                    @include('admin.partials.actor-avatar', [
                                        'imageUrl' => $customer->profileImageUrl(),
                                        'label' => $customer->name,
                                    ])
                                    <span class="font-semibold text-slate-900" title="{{ $customer->name }}">{{ $customer->name }}</span>
                                </div>
                            </td>
                            <td>{{ $customer->mobile }}</td>
                            <td>{{ $customer->city ?? '—' }}</td>
                            <td class="jb-col-date text-sm text-slate-600">{{ $customer->registered_at?->format('M d, Y') ?? '—' }}</td>
                            <td class="text-center">{{ $customer->total_orders }}</td>
                            <td class="jb-col-status">@include('admin.components.status-badge', ['status' => $customer->status])</td>
                            <td class="jb-table-actions-col">
                                <div class="jb-actions">
                                    <x-admin.action-btn variant="view" :href="route('admin.customers.show', $customer)" />
                                    @if (auth('admin')->user()->hasPermission('customers', 'edit'))
                                        <x-admin.action-btn variant="edit" :href="route('admin.customers.edit', $customer)" />
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="jb-table-empty">No customers match your filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($customers->hasPages())
            {{ $customers->links() }}
        @endif
    </div>
@endsection
