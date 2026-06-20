@extends('admin.layouts.app')

@section('title', $payout->payout_code)
@section('page_title', 'Payout details')
@section('page_subtitle', $payout->payout_code)
@section('back_href', route('admin.payouts.index'))

@php
    $isPaid = $payout->status === 'paid';
    $canRecordPayment = ! $isPaid && auth('admin')->user()->hasPermission('payouts', 'edit');
    $commissionRate = $payout->gross_amount > 0
        ? round(($payout->commission_amount / $payout->gross_amount) * 100, 1)
        : 0;
@endphp

@section('content')
    <div class="jb-payout-header">
        <div>
            <p class="jb-payout-code">{{ $payout->payout_code }}</p>
            <div class="jb-payout-header-badges">
                @include('admin.components.status-badge', ['status' => $payout->status, 'label' => ucfirst($payout->status)])
            </div>
        </div>
        <p class="jb-payout-created">Created {{ $payout->created_at->format('M d, Y · h:i A') }}</p>
    </div>

    <div class="jb-booking-layout">
        <div class="jb-booking-main">
            <div class="jb-payout-amount-card @if ($isPaid) jb-payout-amount-card--paid @endif">
                <p class="jb-payout-amount-label">Net payout</p>
                <p class="jb-payout-amount-value">₹{{ number_format($payout->net_amount, 2) }}</p>
                <div class="jb-payout-breakdown">
                    <div class="jb-payout-breakdown-item">
                        <span class="jb-payout-breakdown-label">Gross earnings</span>
                        <span class="jb-payout-breakdown-value">₹{{ number_format($payout->gross_amount, 2) }}</span>
                    </div>
                    <span class="jb-payout-breakdown-op" aria-hidden="true">−</span>
                    <div class="jb-payout-breakdown-item">
                        <span class="jb-payout-breakdown-label">Commission @if ($commissionRate > 0)<span class="jb-payout-breakdown-muted">({{ $commissionRate }}%)</span>@endif</span>
                        <span class="jb-payout-breakdown-value jb-payout-breakdown-value--deduct">₹{{ number_format($payout->commission_amount, 2) }}</span>
                    </div>
                    <span class="jb-payout-breakdown-op" aria-hidden="true">=</span>
                    <div class="jb-payout-breakdown-item jb-payout-breakdown-item--total">
                        <span class="jb-payout-breakdown-label">Vendor receives</span>
                        <span class="jb-payout-breakdown-value">₹{{ number_format($payout->net_amount, 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="jb-booking-card">
                <h3 class="jb-booking-card-title">Vendor</h3>
                <div class="jb-booking-designer">
                    @include('admin.partials.actor-avatar', [
                        'imageUrl' => $payout->vendor->profileImageUrl(),
                        'fallbackUrl' => $payout->vendor->shopLogoUrl(),
                        'label' => $payout->vendor->brand_name,
                        'size' => 'md',
                    ])
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('admin.vendors.show', $payout->vendor) }}" class="jb-booking-designer-name" title="{{ $payout->vendor->brand_name }}">{{ $payout->vendor->brand_name }}</a>
                        <p class="jb-booking-designer-meta">{{ $payout->vendor->vendor_code }}</p>
                    </div>
                </div>
            </div>

            @if ($isPaid || $payout->reference || $payout->notes)
                <div class="jb-booking-card">
                    <h3 class="jb-booking-card-title">Payment record</h3>
                    <dl class="jb-dl jb-dl--grid">
                        <div>
                            <dt>Bank / UTR reference</dt>
                            <dd>{{ $payout->reference ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt>Paid on</dt>
                            <dd>{{ $payout->paid_at?->format('M d, Y · h:i A') ?? '—' }}</dd>
                        </div>
                        @if ($payout->notes)
                            <div class="sm:col-span-2">
                                <dt>Notes</dt>
                                <dd class="jb-textarea-break">{{ $payout->notes }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @endif
        </div>

        <aside class="jb-booking-sidebar">
            @if ($isPaid)
                <div class="jb-payout-paid-card" style="margin-bottom: 15px;">
                    <div class="jb-payout-paid-icon" aria-hidden="true">
                        <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="jb-payout-paid-title">Payment completed</p>
                    <p class="jb-payout-paid-text">₹{{ number_format($payout->net_amount, 2) }} was sent to {{ $payout->vendor->brand_name }}.</p>
                    @if ($payout->paid_at)
                        <p class="jb-payout-paid-meta">Marked paid {{ $payout->paid_at->format('M d, Y · h:i A') }}</p>
                    @endif
                    @if ($payout->reference)
                        <p class="jb-payout-paid-ref">Ref: <span>{{ $payout->reference }}</span></p>
                    @endif
                </div>
            @elseif ($canRecordPayment)
                <div class="jb-payout-record-card" style="margin-bottom: 15px;">
                    <div class="jb-payout-record-head">
                        <p class="jb-payout-record-title">Record payment</p>
                        <p class="jb-payout-record-sub">Confirm once the transfer to the vendor is complete.</p>
                    </div>
                    <form method="POST" action="{{ route('admin.payouts.mark-paid', $payout) }}" class="jb-payout-record-form">
                        @csrf
                        <div class="jb-payout-record-summary">
                            <span>Amount to pay</span>
                            <strong>₹{{ number_format($payout->net_amount, 2) }}</strong>
                        </div>
                        @include('admin.partials.form-input', ['label' => 'Bank / UTR reference', 'name' => 'reference', 'value' => old('reference', $payout->reference), 'placeholder' => 'e.g. UTR or transaction ID'])
                        @include('admin.partials.form-input', ['label' => 'Notes (optional)', 'name' => 'notes', 'type' => 'textarea', 'value' => old('notes', $payout->notes), 'full' => true, 'placeholder' => 'Internal note about this transfer'])
                        <x-admin.button variant="primary" type="submit" class="w-full justify-center">Confirm payment</x-admin.button>
                    </form>
                </div>
            @else
                <div class="jb-booking-card" style="margin-bottom: 15px;">
                    <h3 class="jb-booking-card-title">Status</h3>
                    <p class="text-sm text-slate-600">This payout is {{ str_replace('_', ' ', $payout->status) }}. No further action is available.</p>
                </div>
            @endif
        </aside>
    </div>
@endsection
