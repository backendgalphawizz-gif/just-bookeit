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
    <div class="jb-dispute-layout">
        <div class="jb-detail-card">
            <h2>Dispute information</h2>
            <dl class="jb-dl">
                <div><dt>Category</dt><dd>{{ $dispute->category?->name ?? $dispute->order->category?->name ?? '—' }}</dd></div>
                <div><dt>Order</dt><dd><a href="{{ route('admin.orders.show', $dispute->order) }}" class="jb-link">{{ $dispute->order->order_number }}</a></dd></div>
                <div><dt>Raised by</dt><dd>{{ ucfirst($dispute->raised_by) }}</dd></div>
                <div><dt>Status</dt><dd>@include('admin.components.status-badge', ['status' => $dispute->status])</dd></div>
                <div><dt>Customer</dt><dd>{{ $dispute->order->customer->name }}</dd></div>
                @if ($dispute->order->vendor)
                    <div><dt>Vendor</dt><dd>{{ $dispute->order->vendor->brand_name }}</dd></div>
                @endif
                <div><dt>Chat</dt><dd>{{ $dispute->isChatOpen() ? 'Open — admin & customer can message' : 'Closed' }}</dd></div>
            </dl>
        </div>

        @include('admin.disputes.partials.chat')
    </div>
@endsection
