@extends('admin.layouts.app')
@section('title', 'Dispute #'.$dispute->id)
@section('page_title', $dispute->subject)
@section('back_href', route('admin.disputes.index'))
@section('header_actions')
    @if (auth('admin')->user()->hasPermission('disputes', 'edit'))
        <x-admin.button variant="secondary" :href="route('admin.disputes.edit', $dispute)">Edit</x-admin.button>
    @endif
@endsection
@section('content')
    <div class="jb-detail-card max-w-2xl">
        <h2>Dispute information</h2>
        <dl class="jb-dl">
            <div><dt>Order</dt><dd><a href="{{ route('admin.orders.show', $dispute->order) }}" class="jb-link">{{ $dispute->order->order_number }}</a></dd></div>
            <div><dt>Raised by</dt><dd>{{ ucfirst($dispute->raised_by) }}</dd></div>
            <div><dt>Status</dt><dd>@include('admin.components.status-badge', ['status' => $dispute->status])</dd></div>
            <div><dt>Customer</dt><dd>{{ $dispute->order->customer->name }}</dd></div>
            @if($dispute->order->vendor)<div><dt>Vendor</dt><dd>{{ $dispute->order->vendor->brand_name }}</dd></div>@endif
        </dl>
    </div>

    @php
        $disputeActions = [];
        if (auth('admin')->user()->hasPermission('disputes', 'edit')) {
            if (in_array($dispute->status, ['raised', 'under_review'], true)) {
                $disputeActions[] = ['label' => 'Resolve', 'url' => route('admin.disputes.resolve', $dispute), 'variant' => 'success'];
                $disputeActions[] = ['label' => 'Close', 'url' => route('admin.disputes.close', $dispute), 'variant' => 'secondary', 'confirm' => 'Close this dispute?'];
            } elseif ($dispute->status === 'resolved') {
                $disputeActions[] = ['label' => 'Close', 'url' => route('admin.disputes.close', $dispute), 'variant' => 'secondary'];
            }
        }
    @endphp
    @include('admin.partials.status-actions-panel', ['title' => 'Dispute resolution', 'actions' => $disputeActions])
@endsection
