@extends('admin.layouts.app')
@section('title', 'Refund #'.$refund->id)
@section('page_title', 'Refund #'.$refund->id)
@section('header_actions')
    @if (auth('admin')->user()->hasPermission('refunds', 'edit'))
        <x-admin.button variant="secondary" :href="route('admin.refunds.edit', $refund)">Edit</x-admin.button>
    @endif
@endsection
@section('content')
    <div class="jb-detail-card max-w-2xl">
        <h2>Refund details</h2>
        <dl class="jb-dl">
            <div><dt>Customer</dt><dd>{{ $refund->customer->name }}</dd></div>
            <div><dt>Order</dt><dd><a href="{{ route('admin.orders.show', $refund->order) }}" class="jb-link">{{ $refund->order->order_number }}</a></dd></div>
            <div><dt>Amount</dt><dd class="text-lg font-bold">₹{{ number_format($refund->amount, 2) }}</dd></div>
            <div><dt>Status</dt><dd>@include('admin.components.status-badge', ['status' => $refund->status])</dd></div>
            <div><dt>Reason</dt><dd>{{ $refund->reason ?? '—' }}</dd></div>
        </dl>
    </div>

    @php
        $refundActions = [];
        if (auth('admin')->user()->hasPermission('refunds', 'edit')) {
            if (in_array($refund->status, ['requested', 'under_review'], true)) {
                $refundActions[] = ['label' => 'Approve', 'url' => route('admin.refunds.approve', $refund), 'variant' => 'success'];
                $refundActions[] = ['label' => 'Reject', 'url' => route('admin.refunds.reject', $refund), 'variant' => 'danger', 'confirm' => 'Reject this refund?'];
            }
            if ($refund->status === 'approved') {
                $refundActions[] = ['label' => 'Mark processed', 'url' => route('admin.refunds.process', $refund), 'variant' => 'primary', 'confirm' => 'Mark refund as processed and update the order?'];
            }
        }
    @endphp
    @include('admin.partials.status-actions-panel', ['title' => 'Refund decision', 'actions' => $refundActions])
@endsection
