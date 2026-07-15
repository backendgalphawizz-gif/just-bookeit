@extends('admin.layouts.app')

@section('title', $withdrawal->request_code)
@section('page_title', 'Withdrawal request')
@section('page_subtitle', $withdrawal->request_code)
@section('back_href', route('admin.withdrawals.index'))

@php
    $canReview = $withdrawal->isPending() && auth('admin')->user()->hasPermission('payouts', 'edit');
@endphp

@section('content')
    <div class="jb-payout-header">
        <div>
            <p class="jb-payout-code">{{ $withdrawal->request_code }}</p>
            <div class="jb-payout-header-badges">
                @include('admin.components.status-badge', ['status' => $withdrawal->status, 'label' => $withdrawal->statusLabel()])
            </div>
        </div>
        <p class="jb-payout-created">Requested {{ $withdrawal->created_at->format('M d, Y · h:i A') }}</p>
    </div>

    <div class="jb-booking-layout">
        <div class="jb-booking-main">
            <div class="jb-payout-amount-card @if ($withdrawal->status === 'approved') jb-payout-amount-card--paid @endif">
                <p class="jb-payout-amount-label">Withdrawal amount</p>
                <p class="jb-payout-amount-value">₹{{ number_format($withdrawal->amount, 2) }}</p>
            </div>

            <div class="jb-booking-card">
                <h3 class="jb-booking-card-title">Vendor</h3>
                <div class="jb-booking-designer">
                    @include('admin.partials.actor-avatar', [
                        'imageUrl' => $withdrawal->vendor->profileImageUrl(),
                        'fallbackUrl' => $withdrawal->vendor->shopLogoUrl(),
                        'label' => $withdrawal->vendor->brand_name,
                        'size' => 'md',
                    ])
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('admin.vendors.show', $withdrawal->vendor) }}" class="jb-booking-designer-name">{{ $withdrawal->vendor->brand_name }}</a>
                        <p class="jb-booking-designer-meta">
                            Actual wallet ₹{{ number_format($withdrawal->vendor->wallet_balance, 2) }}
                            · Available ₹{{ number_format($available, 2) }}
                        </p>
                        <p class="jb-booking-designer-meta">
                            {{ $withdrawal->vendor->bank_name ?? 'Bank —' }}
                            @if ($withdrawal->vendor->account_number)
                                · ****{{ substr((string) $withdrawal->vendor->account_number, -4) }}
                            @endif
                            @if ($withdrawal->vendor->ifsc_code)
                                · {{ $withdrawal->vendor->ifsc_code }}
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            @if ($withdrawal->vendor_note)
                <div class="jb-booking-card">
                    <h3 class="jb-booking-card-title">Vendor note</h3>
                    <p class="jb-textarea-break">{{ $withdrawal->vendor_note }}</p>
                </div>
            @endif

            @if (! $withdrawal->isPending())
                <div class="jb-booking-card">
                    <h3 class="jb-booking-card-title">Admin decision</h3>
                    <dl class="jb-dl jb-dl--grid">
                        <div>
                            <dt>Reviewed by</dt>
                            <dd>{{ $withdrawal->reviewedByAdmin?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt>Reviewed at</dt>
                            <dd>{{ $withdrawal->reviewed_at?->format('M d, Y · h:i A') ?? '—' }}</dd>
                        </div>
                        @if ($withdrawal->payment_reference)
                            <div>
                                <dt>Payment reference</dt>
                                <dd>{{ $withdrawal->payment_reference }}</dd>
                            </div>
                        @endif
                        <div class="sm:col-span-2">
                            <dt>Admin note</dt>
                            <dd class="jb-textarea-break">{{ $withdrawal->admin_note ?: '—' }}</dd>
                        </div>
                    </dl>
                </div>
            @endif
        </div>

        <aside class="jb-booking-sidebar">
            @if ($canReview)
                <div class="jb-booking-card" style="margin-bottom:1rem">
                    <h3 class="jb-booking-card-title">Approve withdrawal</h3>
                    <p class="text-sm text-slate-500" style="margin-bottom:.75rem">Pays out from actual wallet and marks request approved.</p>
                    <form method="POST" action="{{ route('admin.withdrawals.approve', $withdrawal) }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="jb-label" for="approve_note">Admin note</label>
                            <textarea id="approve_note" name="admin_note" class="jb-input @error('admin_note') jb-input--error @enderror" rows="3" required minlength="5" maxlength="1000" placeholder="e.g. Transferred to bank account">{{ old('admin_note') }}</textarea>
                            @error('admin_note')
                                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="jb-label" for="payment_reference">Payment / UTR reference (optional)</label>
                            <input id="payment_reference" type="text" name="payment_reference" value="{{ old('payment_reference') }}" class="jb-input @error('payment_reference') jb-input--error @enderror" maxlength="100">
                            @error('payment_reference')
                                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <x-admin.button variant="primary" type="submit" class="w-full">Approve &amp; pay</x-admin.button>
                    </form>
                </div>

                <div class="jb-booking-card">
                    <h3 class="jb-booking-card-title">Reject withdrawal</h3>
                    <form method="POST" action="{{ route('admin.withdrawals.reject', $withdrawal) }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="jb-label" for="reject_note">Rejection note</label>
                            <textarea id="reject_note" name="admin_note" class="jb-input @error('admin_note') jb-input--error @enderror" rows="3" required minlength="5" maxlength="1000" placeholder="Reason for rejection">{{ old('admin_note') }}</textarea>
                            @error('admin_note')
                                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <x-admin.button variant="danger" type="submit" class="w-full">Reject request</x-admin.button>
                    </form>
                </div>
            @elseif ($withdrawal->status === 'approved')
                <div class="jb-payout-paid-card">
                    <p class="jb-payout-paid-title">Approved &amp; paid</p>
                    <p class="jb-payout-paid-text">₹{{ number_format($withdrawal->amount, 2) }} deducted from actual wallet.</p>
                </div>
            @elseif ($withdrawal->status === 'rejected')
                <div class="jb-booking-card">
                    <h3 class="jb-booking-card-title">Rejected</h3>
                    <p class="text-sm text-slate-600">{{ $withdrawal->admin_note }}</p>
                </div>
            @endif
        </aside>
    </div>
@endsection
