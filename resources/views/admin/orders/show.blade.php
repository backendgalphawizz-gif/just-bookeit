@extends('admin.layouts.app')
@section('title', $order->order_number)
@section('page_title', $order->order_number)
@section('page_subtitle', 'Placed '.$order->created_at->format('M d, Y h:i A'))
@section('header_actions')
    @if (auth('admin')->user()->hasPermission('orders', 'edit'))
        <x-admin.button variant="secondary" :href="route('admin.orders.edit', $order)">Edit Order</x-admin.button>
    @endif
@endsection
@section('content')
    <div class="jb-detail-grid">
        <div class="jb-detail-card">
            <h2>Customer & Vendor</h2>
            <dl class="jb-dl">
                <div><dt>Customer</dt><dd><a href="{{ route('admin.customers.show', $order->customer) }}" class="jb-link">{{ $order->customer->name }}</a></dd></div>
                <div><dt>Vendor</dt><dd>@if($order->vendor)<a href="{{ route('admin.vendors.show', $order->vendor) }}" class="jb-link">{{ $order->vendor->brand_name }}</a>@else Unassigned @endif</dd></div>
                <div><dt>Category</dt><dd>{{ $order->category->name }}</dd></div>
            </dl>
        </div>
        <div class="jb-detail-card">
            <h2>Payment & Status</h2>
            <dl class="jb-dl">
                <div><dt>Amount</dt><dd class="text-lg font-bold">₹{{ number_format($order->amount, 2) }}</dd></div>
                <div><dt>Payment</dt><dd>@include('admin.components.status-badge', ['status' => $order->payment_status, 'label' => ucfirst($order->payment_status)])</dd></div>
                <div><dt>Order status</dt><dd>@include('admin.components.status-badge', ['status' => $order->status])</dd></div>
                @if ($order->refund)<div><dt>Refund</dt><dd><a href="{{ route('admin.refunds.show', $order->refund) }}" class="jb-link">{{ ucfirst($order->refund->status) }}</a></dd></div>@endif
                @if ($order->dispute)<div><dt>Dispute</dt><dd><a href="{{ route('admin.disputes.show', $order->dispute) }}" class="jb-link">{{ $order->dispute->subject }}</a></dd></div>@endif
            </dl>
        </div>
    </div>

    @php
        $orderActions = [];
        if (auth('admin')->user()->hasPermission('orders', 'edit')) {
            $orderRoute = fn (string $status) => route('admin.orders.update-status', $order);
            $orderActions = match ($order->status) {
                'new' => [
                    ['label' => 'Send to vendor', 'url' => $orderRoute('pending_acceptance'), 'status' => 'pending_acceptance', 'variant' => 'primary'],
                    ['label' => 'Accept', 'url' => $orderRoute('accepted'), 'status' => 'accepted', 'variant' => 'success'],
                    ['label' => 'Cancel', 'url' => $orderRoute('cancelled'), 'status' => 'cancelled', 'variant' => 'danger', 'confirm' => 'Cancel this order?'],
                ],
                'pending_acceptance' => [
                    ['label' => 'Accept', 'url' => $orderRoute('accepted'), 'status' => 'accepted', 'variant' => 'success'],
                    ['label' => 'Cancel', 'url' => $orderRoute('cancelled'), 'status' => 'cancelled', 'variant' => 'danger', 'confirm' => 'Cancel this order?'],
                ],
                'accepted' => [
                    ['label' => 'Start work', 'url' => $orderRoute('in_progress'), 'status' => 'in_progress', 'variant' => 'primary'],
                    ['label' => 'Cancel', 'url' => $orderRoute('cancelled'), 'status' => 'cancelled', 'variant' => 'danger', 'confirm' => 'Cancel this order?'],
                ],
                'in_progress' => [
                    ['label' => 'Mark in transit', 'url' => $orderRoute('in_transit'), 'status' => 'in_transit', 'variant' => 'primary'],
                    ['label' => 'Cancel', 'url' => $orderRoute('cancelled'), 'status' => 'cancelled', 'variant' => 'danger', 'confirm' => 'Cancel this order?'],
                ],
                'in_transit' => [
                    ['label' => 'Mark delivered', 'url' => $orderRoute('delivered'), 'status' => 'delivered', 'variant' => 'success'],
                ],
                default => [],
            };
        }
    @endphp
    @include('admin.partials.status-actions-panel', ['title' => 'Order workflow', 'actions' => $orderActions])
@endsection
