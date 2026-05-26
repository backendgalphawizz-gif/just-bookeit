@extends('admin.layouts.app')
@section('title', 'Payment '.$order->order_number)
@section('page_title', 'Payment details')
@section('page_subtitle', $order->order_number)
@section('header_actions')
    <x-admin.button variant="secondary" :href="route('admin.orders.show', $order)">View order</x-admin.button>
@endsection
@section('content')
    <div class="jb-detail-card max-w-xl">
        <h2>Transaction summary</h2>
        <dl class="jb-dl">
            <div><dt>Amount</dt><dd class="text-2xl font-bold text-slate-900">₹{{ number_format($order->amount, 2) }}</dd></div>
            <div><dt>Payment status</dt><dd>@include('admin.components.status-badge', ['status' => $order->payment_status, 'label' => ucfirst($order->payment_status)])</dd></div>
            <div><dt>Order status</dt><dd>@include('admin.components.status-badge', ['status' => $order->status])</dd></div>
            <div><dt>Customer</dt><dd>{{ $order->customer->name }}</dd></div>
            <div><dt>Vendor</dt><dd>{{ $order->vendor?->brand_name ?? 'Unassigned' }}</dd></div>
            <div><dt>Category</dt><dd>{{ $order->category->name }}</dd></div>
            <div><dt>Date</dt><dd>{{ $order->created_at->format('M d, Y h:i A') }}</dd></div>
        </dl>
    </div>
@endsection
