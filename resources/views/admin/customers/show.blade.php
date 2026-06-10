@extends('admin.layouts.app')
@section('title', $customer->name)
@section('page_title')
    <span class="block max-w-full truncate" title="{{ $customer->name }}">{{ $customer->name }}</span>
@endsection
@section('page_subtitle', $customer->customer_code)
@section('back_href', route('admin.customers.index'))

@section('header_actions')
    <x-admin.account-history :histories="$customer->statusHistories" title="Customer account history" />
    @if (auth('admin')->user()->hasPermission('customers', 'edit'))
        @if ($customer->status !== 'active')
            <form method="POST" action="{{ route('admin.customers.activate', $customer) }}" class="inline-flex">@csrf
                <x-admin.button variant="success" type="submit">Activate</x-admin.button>
            </form>
        @endif
        @if ($customer->status === 'active')
            <form
                method="POST"
                action="{{ route('admin.customers.suspend', $customer) }}"
                class="inline-flex"
                data-jb-confirm="This customer will be suspended. The reason you enter will be visible to them."
                data-jb-confirm-title="Suspend customer"
                data-jb-confirm-variant="error"
                data-jb-confirm-label="Suspend"
                data-jb-confirm-requires-reason="Reason for suspension"
            >
                @csrf
                <x-admin.button variant="danger" type="submit">Suspend</x-admin.button>
            </form>
            <form
                method="POST"
                action="{{ route('admin.customers.block', $customer) }}"
                class="inline-flex"
                data-jb-confirm="This customer will be blocked. The reason you enter will be visible to them."
                data-jb-confirm-title="Block customer"
                data-jb-confirm-variant="error"
                data-jb-confirm-label="Block"
                data-jb-confirm-requires-reason="Reason for blocking"
            >
                @csrf
                <x-admin.button variant="danger" type="submit">Block</x-admin.button>
            </form>
        @endif
        <x-admin.button variant="secondary" :href="route('admin.customers.edit', $customer)">Edit</x-admin.button>
    @endif
    @if (auth('admin')->user()->hasPermission('customers', 'delete'))
        <form
            method="POST"
            action="{{ route('admin.customers.destroy', $customer) }}"
            class="inline-flex"
            data-jb-confirm="This customer will be permanently deleted."
            data-jb-confirm-title="Delete customer?"
            data-jb-confirm-variant="error"
            data-jb-confirm-label="Delete"
        >
            @csrf @method('DELETE')
            <x-admin.button variant="danger" type="submit">Delete</x-admin.button>
        </form>
    @endif
@endsection

@section('content')
    @if (in_array($customer->status, ['suspended', 'blocked'], true))
        <div class="jb-card mb-6 border-rose-200 bg-rose-50/80">
            <div class="jb-card-body">
                <p class="text-sm font-bold uppercase tracking-wide text-rose-800">
                    Account {{ $customer->status === 'blocked' ? 'blocked' : 'suspended' }}
                </p>
                <p class="mt-2 text-sm leading-relaxed text-rose-950">{{ $customer->rejection_reason ?: 'No reason recorded.' }}</p>
            </div>
        </div>
    @endif

    <div class="jb-detail-grid">
        <div class="jb-detail-card">
            <h2>Basic Details</h2>
            <x-admin.actor-profile-header
                :image-url="$customer->profileImageUrl()"
                :title="$customer->name"
                :subtitle="$customer->customer_code"
            >
                @include('admin.components.status-badge', ['status' => $customer->status])
            </x-admin.actor-profile-header>
            <dl class="jb-dl">
                <div><dt>Mobile No</dt><dd>{{ $customer->mobile }}</dd></div>
                <div><dt>Email ID</dt><dd>{{ $customer->email ?? '—' }}</dd></div>
                <div><dt>City</dt><dd>{{ $customer->city ?? '—' }}</dd></div>
                <div><dt>Verified</dt><dd>{{ $customer->is_verified ? 'Yes' : 'No' }}</dd></div>
                <div><dt>Registered</dt><dd>{{ $customer->registered_at?->format('M d, Y') }}</dd></div>
            </dl>
        </div>
        <div class="jb-detail-card lg:col-span-2">
            <h2>Order History</h2>
            <div class="jb-table-wrap mt-4">
                <table class="jb-table">
                    <thead><tr>
                        @include('admin.partials.table-index-header')
                        <th class="jb-col-id">Order</th>
                        <th class="jb-col-name">Vendor</th>
                        <th class="jb-col-amount">Amount</th>
                        <th class="jb-col-status">Status</th>
                        <th class="jb-table-actions-col">Actions</th>
                    </tr></thead>
                    <tbody>
                        @forelse ($customer->orders as $order)
                            <tr>
                                @include('admin.partials.table-index-cell')
                                <td class="jb-col-id font-semibold">{{ $order->order_number }}</td>
                                <td class="jb-col-name max-w-[14rem]">
                                    <span class="block truncate font-medium" title="{{ $order->vendor?->brand_name ?? 'Unassigned' }}">{{ $order->vendor?->brand_name ?? 'Unassigned' }}</span>
                                </td>
                                <td class="jb-col-amount">₹{{ number_format($order->amount, 2) }}</td>
                                <td class="jb-col-status">@include('admin.components.status-badge', ['status' => $order->status])</td>
                                <td class="jb-table-actions-col"><div class="jb-actions"><x-admin.action-btn variant="view" :href="route('admin.orders.show', $order)" /></div></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="jb-table-empty">No orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
