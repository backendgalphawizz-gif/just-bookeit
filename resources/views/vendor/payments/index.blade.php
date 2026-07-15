@extends('vendor.layouts.app')

@section('title', 'Payment Management')

@section('content')
@php
    $tab = request('tab', 'withdrawals');
    if (! in_array($tab, ['withdrawals', 'wallet', 'payments'], true)) {
        $tab = 'withdrawals';
    }
    $pendingWithdrawals = $withdrawals->where('status', 'pending')->count();
@endphp

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
        <p class="vp-wallet-card-note">Customer payments are held here for {{ \App\Services\Vendor\VendorWalletService::holdDays() }} days before release.</p>
    </div>
    <div class="vp-wallet-card vp-wallet-card--actual">
        <div class="vp-wallet-card-label">Actual Wallet</div>
        <div class="vp-wallet-card-value">₹{{ number_format($vendor->wallet_balance, 0) }}</div>
        <p class="vp-wallet-card-note">
            Available to request: ₹{{ number_format($availableForWithdrawal, 0) }}
            @if ($availableForWithdrawal < $vendor->wallet_balance)
                (pending requests reserved)
            @endif
        </p>
    </div>
</div>

<nav class="vp-tabs" style="margin-top:1.25rem;" aria-label="Payment sections">
    <a href="{{ route('vendor.payments.index', ['tab' => 'withdrawals']) }}"
       class="vp-tab {{ $tab === 'withdrawals' ? 'vp-tab--active' : '' }}">
        Withdrawals
        @if ($pendingWithdrawals > 0)
            <span class="vp-badge vp-badge--pending" style="margin-left:.35rem;vertical-align:middle;">{{ $pendingWithdrawals }}</span>
        @endif
    </a>
    <a href="{{ route('vendor.payments.index', array_filter(['tab' => 'wallet', 'from' => request('from'), 'to' => request('to')])) }}"
       class="vp-tab {{ $tab === 'wallet' ? 'vp-tab--active' : '' }}">
        Wallet Activity
    </a>
    <a href="{{ route('vendor.payments.index', array_filter(['tab' => 'payments', 'search' => request('search'), 'from' => request('from'), 'to' => request('to')])) }}"
       class="vp-tab {{ $tab === 'payments' ? 'vp-tab--active' : '' }}">
        Customer Payments
    </a>
</nav>

@if ($tab === 'withdrawals')
    <div class="vp-card" style="padding:1.15rem 1.25rem;">
        <div style="display:flex;flex-wrap:wrap;gap:1.25rem;align-items:flex-start;justify-content:space-between;">
            <div style="flex:1;min-width:220px;">
                <h2 style="margin:0 0 .35rem;font-size:1.05rem;font-weight:700;">Request withdrawal</h2>
                <p style="margin:0;color:var(--vp-muted);font-size:.875rem;line-height:1.5;">
                    Submit a request from your actual wallet. Admin will approve or reject with a note.
                </p>
            </div>
            <form method="POST" action="{{ route('vendor.payments.withdraw') }}" style="flex:1;min-width:260px;display:grid;gap:.75rem;">
                @csrf
                <div>
                    <label class="vp-label" for="withdraw_amount">Amount (₹)</label>
                    <input id="withdraw_amount" type="number" name="amount" class="vp-input" min="1" step="0.01"
                           max="{{ max(1, $availableForWithdrawal) }}"
                           value="{{ old('amount') }}"
                           placeholder="Max ₹{{ number_format($availableForWithdrawal, 0) }}"
                           @disabled($availableForWithdrawal < 1)
                           required>
                </div>
                <div>
                    <label class="vp-label" for="vendor_note">Note (optional)</label>
                    <textarea id="vendor_note" name="vendor_note" class="vp-input" rows="2" maxlength="500" placeholder="Bank / account reminder">{{ old('vendor_note') }}</textarea>
                </div>
                <button type="submit" class="vp-btn vp-btn--primary" @disabled($availableForWithdrawal < 1)>
                    Submit withdrawal request
                </button>
            </form>
        </div>
    </div>

    <div class="vp-card" style="margin-top:1rem;">
        <div class="vp-card-head">
            <h3>My withdrawal requests</h3>
            <span class="vp-card-count">{{ $withdrawals->count() }} recent</span>
        </div>
        <div class="vp-table-wrap">
            <table class="vp-table">
                <thead>
                    <tr>
                        <th>Request</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Note</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($withdrawals as $withdrawal)
                        <tr>
                            <td><strong>{{ $withdrawal->request_code }}</strong></td>
                            <td>₹{{ number_format($withdrawal->amount, 0) }}</td>
                            <td>
                                @if ($withdrawal->status === 'approved')
                                    <span class="vp-badge vp-badge--done">{{ $withdrawal->statusLabel() }}</span>
                                @elseif ($withdrawal->status === 'rejected')
                                    <span class="vp-badge vp-badge--danger">{{ $withdrawal->statusLabel() }}</span>
                                @else
                                    <span class="vp-badge vp-badge--pending">{{ $withdrawal->statusLabel() }}</span>
                                @endif
                            </td>
                            <td class="vp-td-note" title="{{ $withdrawal->admin_note ?: ($withdrawal->vendor_note ?: '') }}">
                                @if ($withdrawal->admin_note)
                                    Admin: {{ \Illuminate\Support\Str::limit($withdrawal->admin_note, 120) }}
                                @elseif ($withdrawal->vendor_note)
                                    {{ \Illuminate\Support\Str::limit($withdrawal->vendor_note, 120) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $withdrawal->created_at?->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="vp-empty">No withdrawal requests yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($payouts->isNotEmpty())
        <div class="vp-card" style="margin-top:1rem;">
            <div class="vp-card-head">
                <h3>Recent payouts</h3>
            </div>
            <div class="vp-card-pad">
                @foreach ($payouts as $payout)
                    <div class="vp-payout-row">
                        <span style="font-weight:600;">{{ $payout->payout_code }}</span>
                        <strong>₹{{ number_format($payout->net_amount, 0) }}</strong>
                        <span class="vp-badge vp-badge--pending">{{ ucfirst($payout->status) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

@elseif ($tab === 'wallet')
    @push('filter_actions')
        <x-vendor.export-dropdown module="wallet" :params="['tab', 'from', 'to']" />
    @endpush

    <form method="GET" class="vp-filters vp-card" style="padding: 1rem;">
        <input type="hidden" name="tab" value="wallet">
        <div class="vp-filters-grid">
            @include('vendor.partials.date-filter')
            @include('vendor.partials.filters-end', ['resetUrl' => route('vendor.payments.index', ['tab' => 'wallet'])])
        </div>
    </form>

    <div class="vp-card" style="margin-top: 1rem;">
        <div class="vp-card-head">
            <h3>Wallet Activity</h3>
            <span class="vp-card-count">{{ $walletTransactions->total() }} entries</span>
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
            <div class="vp-card-pad">{{ $walletTransactions->appends(request()->query())->links('vendor.pagination.default') }}</div>
        @endif
    </div>

@else
    @push('filter_actions')
        <x-vendor.export-dropdown module="payments" :params="['tab', 'search', 'from', 'to']" />
    @endpush

    <form method="GET" class="vp-filters vp-card" style="padding: 1rem;">
        <input type="hidden" name="tab" value="payments">
        <div class="vp-filters-grid">
            <div class="vp-filters-field vp-filters-field--wide">
                <label class="vp-label" for="payment-search">Search</label>
                <input type="text" id="payment-search" name="search" value="{{ request('search') }}" class="vp-input" placeholder="Transaction, booking, customer...">
            </div>
            @include('vendor.partials.date-filter')
            @include('vendor.partials.filters-end', ['resetUrl' => route('vendor.payments.index', ['tab' => 'payments'])])
        </div>
    </form>

    <div class="vp-card" style="margin-top: 1rem;">
        <div class="vp-card-head">
            <h3>Customer Payments</h3>
            <span class="vp-card-count">{{ $transactions->total() }} transactions</span>
        </div>
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
                                    <span class="vp-badge vp-badge--danger">Refunded</span>
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
            <div class="vp-card-pad">{{ $transactions->appends(request()->query())->links('vendor.pagination.default') }}</div>
        @endif
    </div>
@endif
@endsection
