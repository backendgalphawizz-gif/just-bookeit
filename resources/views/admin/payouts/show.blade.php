@extends('admin.layouts.app')
@section('title', $payout->payout_code)
@section('page_title', $payout->payout_code)
@section('page_subtitle', $payout->vendor->brand_name)

@section('header_actions')
    <x-admin.button variant="secondary" :href="route('admin.payouts.index')">← Back to list</x-admin.button>
    @if ($payout->status !== 'paid' && auth('admin')->user()->hasPermission('payouts', 'edit'))
        <form method="POST" action="{{ route('admin.payouts.mark-paid', $payout) }}" class="inline-flex">
            @csrf
            <x-admin.button variant="primary" type="submit">Mark as paid</x-admin.button>
        </form>
    @endif
@endsection

@section('content')
    <div class="jb-detail-grid">
        <div class="jb-detail-card">
            <dl class="jb-detail-list">
                <div><dt>Vendor</dt><dd>{{ $payout->vendor->brand_name }}</dd></div>
                <div><dt>Gross amount</dt><dd>₹{{ number_format($payout->gross_amount, 2) }}</dd></div>
                <div><dt>Commission</dt><dd>₹{{ number_format($payout->commission_amount, 2) }}</dd></div>
                <div><dt>Net payout</dt><dd class="font-semibold">₹{{ number_format($payout->net_amount, 2) }}</dd></div>
                <div><dt>Status</dt><dd>@include('admin.components.status-badge', ['status' => $payout->status])</dd></div>
                <div><dt>Reference</dt><dd>{{ $payout->reference ?? '—' }}</dd></div>
                <div><dt>Paid at</dt><dd>{{ $payout->paid_at?->format('M d, Y h:i A') ?? '—' }}</dd></div>
                <div><dt>Created</dt><dd>{{ $payout->created_at->format('M d, Y h:i A') }}</dd></div>
                @if ($payout->notes)
                    <div class="sm:col-span-2"><dt>Notes</dt><dd>{{ $payout->notes }}</dd></div>
                @endif
            </dl>
        </div>
        @if ($payout->status !== 'paid' && auth('admin')->user()->hasPermission('payouts', 'edit'))
            <div class="jb-card">
                <div class="jb-card-header"><p class="jb-card-header-title">Record payment</p></div>
                <form method="POST" action="{{ route('admin.payouts.mark-paid', $payout) }}" class="jb-card-body space-y-4">
                    @csrf
                    @include('admin.partials.form-input', ['label' => 'Bank / UTR reference', 'name' => 'reference', 'value' => old('reference', $payout->reference)])
                    @include('admin.partials.form-input', ['label' => 'Notes', 'name' => 'notes', 'type' => 'textarea', 'value' => old('notes', $payout->notes), 'full' => true])
                    <x-admin.button variant="primary" type="submit">Confirm paid</x-admin.button>
                </form>
            </div>
        @endif
    </div>
@endsection
