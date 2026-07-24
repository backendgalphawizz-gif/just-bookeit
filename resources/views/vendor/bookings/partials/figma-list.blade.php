{{-- Shared Figma-style bookings list. Expects $orders (paginator or collection). --}}
@php
    $tab = request('status', '');
    $tabs = [
        '' => 'All',
        'new' => 'New',
        'pending' => 'Pending',
        'complete' => 'Complete',
    ];
    $statusFilterOptions = [
        '' => 'Status',
        'new' => 'New',
        'pending' => 'Pending',
        'complete' => 'Complete',
        'cancelled' => 'Cancelled',
    ];
    $listRoute = $listRoute ?? route('vendor.bookings.index');
    $showPagination = $showPagination ?? true;
    $embedOnDashboard = $embedOnDashboard ?? false;
@endphp

<div class="vp-card vp-bookings-card">
    <div class="vp-bookings-card-top">
        <h3 class="vp-bookings-card-title">Bookings</h3>

        <form method="GET" action="{{ $listRoute }}" class="vp-bookings-tools" id="vp-bookings-filter-form">
            <div class="vp-bookings-search">
                <svg class="vp-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
                <input
                    type="search"
                    name="search"
                    value="{{ request('search') }}"
                    class="vp-bookings-search-input"
                    placeholder="Search bookings..."
                    autocomplete="off"
                >
            </div>

            <details class="vp-bookings-date-details">
                <summary class="vp-bookings-date-btn">
                    <svg class="vp-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 9.75h18M4.5 6.75h15a1.5 1.5 0 011.5 1.5v12a1.5 1.5 0 01-1.5 1.5h-15a1.5 1.5 0 01-1.5-1.5v-12a1.5 1.5 0 011.5-1.5z"/>
                    </svg>
                    Date
                    @if (request('from') || request('to'))
                        <span class="vp-bookings-date-dot" aria-hidden="true"></span>
                    @endif
                </summary>
                <div class="vp-bookings-date-panel">
                    @include('vendor.partials.date-filter')
                    <div class="vp-bookings-date-actions">
                        <button type="submit" class="vp-btn vp-btn--primary vp-btn--sm">Apply</button>
                        <a href="{{ route('vendor.bookings.index', array_filter(['status' => $tab ?: null, 'search' => request('search') ?: null])) }}" class="vp-btn vp-btn--outline vp-btn--sm">Clear</a>
                    </div>
                </div>
            </details>

            @unless ($embedOnDashboard)
                <label class="vp-sr-only" for="booking-status-filter">Status</label>
                <select
                    id="booking-status-filter"
                    name="status"
                    class="vp-bookings-status-select"
                    onchange="this.form.submit()"
                >
                    @foreach ($statusFilterOptions as $value => $label)
                        <option value="{{ $value }}" @selected((string) $tab === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
            @endunless

            <div class="vp-export-dropdown" x-data="{ open: false }" @click.outside="open = false">
                <button type="button" class="vp-btn vp-btn--export-all" @click="open = !open" aria-haspopup="true" :aria-expanded="open">
                    <svg class="vp-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                    </svg>
                    Export All
                    <span aria-hidden="true">▾</span>
                </button>
                <div class="vp-export-menu" x-show="open" x-cloak x-transition>
                    <a href="{{ route('vendor.list-export', array_merge(['module' => 'bookings', 'format' => 'csv'], request()->only(['search', 'status', 'from', 'to']))) }}" class="vp-export-menu-item">CSV</a>
                    <a href="{{ route('vendor.list-export', array_merge(['module' => 'bookings', 'format' => 'pdf'], request()->only(['search', 'status', 'from', 'to']))) }}" class="vp-export-menu-item">PDF</a>
                </div>
            </div>
        </form>
    </div>

    <nav class="vp-bookings-tabs" aria-label="Booking status">
        @foreach ($tabs as $value => $label)
            @php
                $tabParams = array_filter([
                    'status' => $value !== '' ? $value : null,
                    'search' => request('search') ?: null,
                    'from' => request('from') ?: null,
                    'to' => request('to') ?: null,
                ], fn ($v) => $v !== null && $v !== '');
            @endphp
            <a
                href="{{ $embedOnDashboard ? route('vendor.bookings.index', $tabParams) : route('vendor.bookings.index', $tabParams) }}"
                @class(['vp-bookings-tab', 'is-active' => (string) $tab === (string) $value])
            >{{ $label }}</a>
        @endforeach
    </nav>

    <div class="vp-table-wrap">
        <table class="vp-table vp-table--bookings">
            <thead>
                <tr>
                    <th>Booking info</th>
                    <th>Customer</th>
                    <th>Service type</th>
                    <th>Delivery boy</th>
                    <th>Date &amp; total</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    @php
                        $listStatus = \App\Support\Api\VendorBookingListStatus::resolve($order);
                        $statusSelectClass = match ($listStatus['status']) {
                            'new' => 'vp-status-select--new',
                            'processing', 'pending' => 'vp-status-select--accepted',
                            'complete' => 'vp-status-select--done',
                            'cancelled' => 'vp-status-select--rejected',
                            default => 'vp-status-select--accepted',
                        };
                        $serviceMeta = '—';
                        if ($order->isRental() && $order->rental_start_date && $order->rental_end_date) {
                            $days = $order->rentalDurationDays();
                            $serviceMeta = $order->rental_start_date->format('jS M').' to '.$order->rental_end_date->format('jS M')
                                .($days ? ' ('.$days.' '.Str::plural('day', $days).')' : '');
                        } elseif ($order->isRental() && $order->rentalDurationDays()) {
                            $serviceMeta = $order->rentalDurationDays().' '.Str::plural('day', $order->rentalDurationDays());
                        } elseif (! $order->isRental()) {
                            $serviceMeta = 'Purchase';
                        }
                    @endphp
                    <tr>
                        <td>
                            <div class="vp-table-product">
                                @if ($order->itemImageUrl())
                                    <img src="{{ url($order->itemImageUrl()) }}" alt="" class="vp-thumb panel-lightbox-trigger">
                                @else
                                    <span class="vp-thumb"></span>
                                @endif
                                <div>
                                    <strong>{{ $order->itemDisplayName() }}</strong>
                                    <div class="vp-table-meta">BID-{{ $order->order_number }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="vp-table-customer">
                                <div class="vp-avatar vp-avatar--sm">
                                    @if ($order->customer?->profileImageUrl())
                                        <img src="{{ $order->customer->profileImageUrl() }}" alt="">
                                    @else
                                        {{ strtoupper(substr($order->customer?->name ?? 'C', 0, 1)) }}
                                    @endif
                                </div>
                                <div>
                                    <strong>{{ $order->customer?->name ?? '—' }}</strong>
                                    <div class="vp-table-meta">{{ $order->customer?->mobile ?? '—' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <strong>{{ $order->category?->name ?? 'Rental' }}</strong>
                            <div class="vp-table-meta">{{ $serviceMeta }}</div>
                        </td>
                        <td>
                            @if ($order->driver)
                                <strong>{{ $order->driver->name }}</strong>
                                <div class="vp-table-meta">{{ $order->driver->mobile }}</div>
                            @else
                                <span class="vp-table-meta">Not assigned</span>
                            @endif
                        </td>
                        <td>
                            <div class="vp-table-datetotal">
                                <strong>₹{{ number_format($order->grandTotal(), 0) }}</strong>
                                <div class="vp-table-meta">{{ $order->created_at?->format('M d, Y - g:i A') }}</div>
                            </div>
                        </td>
                        <td>
                            <span class="vp-status-pill {{ $statusSelectClass }}">{{ $listStatus['status_label'] }}</span>
                        </td>
                        <td>
                            <div class="vp-actions vp-actions--bookings">
                                @if ($listStatus['status'] === 'new')
                                    <form method="POST" action="{{ route('vendor.bookings.accept', $order) }}">@csrf
                                        <button type="submit" class="vp-btn vp-btn--primary vp-btn--sm">Accept</button>
                                    </form>
                                    <form method="POST" action="{{ route('vendor.bookings.reject', $order) }}"
                                          data-vp-confirm="This booking will be rejected."
                                          data-vp-confirm-title="Reject booking?"
                                          data-vp-confirm-label="Reject"
                                          data-vp-confirm-variant="error">@csrf
                                        <button type="submit" class="vp-btn vp-btn--danger vp-btn--sm">Reject</button>
                                    </form>
                                @endif
                                <a href="{{ route('vendor.bookings.show', $order) }}" class="vp-btn vp-btn--icon" title="View booking" aria-label="View booking">
                                    @include('vendor.partials.nav-icon', ['icon' => 'eye'])
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="vp-empty-state">
                                <p class="vp-empty-state__title">No bookings found</p>
                                <p class="vp-empty-state__text">
                                    @if (request()->hasAny(['search', 'status', 'from', 'to']))
                                        Try adjusting your filters or <a href="{{ route('vendor.bookings.index') }}">reset them</a>.
                                    @else
                                        New customer orders will appear here.
                                    @endif
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($showPagination && method_exists($orders, 'hasPages') && $orders->hasPages())
        <div class="vp-card-pad">{{ $orders->links('vendor.pagination.default') }}</div>
    @elseif ($embedOnDashboard && $orders->isNotEmpty())
        <div class="vp-bookings-card-foot">
            <a href="{{ route('vendor.bookings.index') }}" class="vp-link-more">View all bookings →</a>
        </div>
    @endif
</div>
