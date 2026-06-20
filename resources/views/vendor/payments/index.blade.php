@extends('vendor.layouts.app')

@section('title', 'Payment Management')

@section('content')
<div class="vp-page-head">
    <div>
        <h1 class="vp-page-title">Payment Management</h1>
        <p class="vp-page-sub">Track your digital hold balance, withdrawable wallet, and transaction history</p>
    </div>
</div>

<div class="vp-wallet-grid">
    <div class="vp-wallet-card vp-wallet-card--digital">
        <div class="vp-wallet-card-label">Digital Wallet</div>
        <div class="vp-wallet-card-value">₹{{ number_format($vendor->digital_wallet_balance, 0) }}</div>
        <p class="vp-wallet-card-note">Customer payments are held here for 15 days before release.</p>
    </div>
    <div class="vp-wallet-card vp-wallet-card--actual">
        <div class="vp-wallet-card-label">Actual Wallet</div>
        <div class="vp-wallet-card-value">₹{{ number_format($vendor->wallet_balance, 0) }}</div>
        <p class="vp-wallet-card-note">Available for withdrawal after the hold period ends.</p>
    </div>
</div>

@push('filter_actions')
    <x-vendor.export-dropdown module="payments" :params="['search', 'from', 'to']" />
@endpush

<form method="GET" class="vp-filters vp-card" style="padding: 1rem;">
    <div class="vp-filters-grid">
        <div class="vp-filters-field vp-filters-field--wide">
            <label class="vp-label" for="payment-search">Search</label>
            <input type="text" id="payment-search" name="search" value="{{ request('search') }}" class="vp-input" placeholder="Transaction, booking, customer...">
        </div>
        @include('vendor.partials.date-filter')
        @include('vendor.partials.filters-end', ['resetUrl' => route('vendor.payments.index')])
    </div>
</form>

<div class="vp-card" style="margin-top: 1rem;">
    <div class="vp-card-head">
        <h3>Wallet Activity</h3>
        <div style="display:flex;align-items:center;gap:.75rem;">
            <span class="vp-card-count">{{ $walletTransactions->total() }} entries</span>
            <x-vendor.export-dropdown module="wallet" :params="['from', 'to']" />
        </div>
    </div>
    <div class="vp-table-wrap">
        <table class="vp-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Wallet</th>
                    <th>Booking</th>
                    <th>Amount</th>
                    <th>Balance After</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($walletTransactions as $entry)
                    <tr>
                        <td>{{ $entry->created_at?->format('M d, Y - g:i A') }}</td>
                        <td>{{ $entry->typeLabel() }}</td>
                        <td>{{ $entry->walletLabel() }}</td>
                        <td>{{ $entry->order?->order_number ?? '—' }}</td>
                        <td>
                            <strong class="{{ $entry->direction === 'credit' ? 'vp-amount--credit' : 'vp-amount--debit' }}">
                                {{ $entry->direction === 'credit' ? '+' : '−' }}₹{{ number_format($entry->amount, 0) }}
                            </strong>
                        </td>
                        <td>₹{{ number_format($entry->balance_after, 0) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="vp-empty">No wallet activity yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($walletTransactions->hasPages())
        <div class="vp-card-pad">{{ $walletTransactions->links('vendor.pagination.default') }}</div>
    @endif
</div>

<div class="vp-page-head" style="margin-top:2rem;">
    <h2 style="margin:0;font-size:1.15rem;font-weight:700;">Customer Payments</h2>
</div>

<div class="vp-card">
    <div class="vp-card-count">{{ $transactions->total() }} transactions</div>
    <div class="vp-table-wrap">
        <table class="vp-table">
            <thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Booking ID</th>
                    <th>User Name</th>
                    <th>Product Name</th>
                    <th>Amount</th>
                    <th>Hold Status</th>
                    <th>Date &amp; Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $txn)
                    <tr>
                        <td><strong>TXN-{{ str_pad($txn->id, 7, '0', STR_PAD_LEFT) }}</strong></td>
                        <td>{{ $txn->order_number }}</td>
                        <td>{{ $txn->customer?->name }}</td>
                        <td>{{ $txn->itemDisplayName() }}</td>
                        <td><strong>₹{{ number_format($txn->grandTotal(), 0) }}</strong></td>
                        <td>
                            @if ($txn->wallet_hold_status === 'held')
                                <span class="vp-badge vp-badge--pending">In digital wallet</span>
                                @if ($txn->wallet_release_at)
                                    <div style="font-size:.72rem;color:var(--vp-muted);margin-top:.2rem;">Releases {{ $txn->wallet_release_at->format('M d, Y') }}</div>
                                @endif
                            @elseif ($txn->wallet_hold_status === 'released')
                                <span class="vp-badge vp-badge--done">In actual wallet</span>
                            @elseif ($txn->wallet_hold_status === 'refunded')
                                <span class="vp-badge vp-badge--cancelled">Refunded</span>
                            @else
                                <span class="vp-badge vp-badge--pending">Pending</span>
                            @endif
                        </td>
                        <td>{{ $txn->created_at?->format('M d, Y - g:i A') }}</td>
                        <td><span class="vp-badge vp-badge--done">Success</span></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="vp-empty">No transactions yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($transactions->hasPages())
        <div class="vp-card-pad">{{ $transactions->links('vendor.pagination.default') }}</div>
    @endif
</div>

@if ($payouts->isNotEmpty())
    <div class="vp-page-head" style="margin-top:2rem;">
        <h2 style="margin:0;font-size:1.15rem;font-weight:700;">Recent Payouts</h2>
    </div>
    <div class="vp-card vp-card-pad">
        @foreach ($payouts as $payout)
            <div class="vp-payout-row">
                <span style="font-weight:600;">{{ $payout->payout_code }}</span>
                <strong>₹{{ number_format($payout->net_amount, 0) }}</strong>
                <span class="vp-badge vp-badge--pending">{{ ucfirst($payout->status) }}</span>
            </div>
        @endforeach
    </div>
@endif
@endsection
