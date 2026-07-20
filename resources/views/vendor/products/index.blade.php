@extends('vendor.layouts.app')

@section('title', $typeLabel)

@section('content')
@php
    $isRental = in_array($type, ['rented-dress', 'rented-jewellery'], true);
    $isRentalDress = $type === 'rented-dress';
    $addLabel = $type === 'rented-dress' ? 'Dresses' : ($type === 'rented-jewellery' ? 'Jewellery' : 'Design');
    $searchPlaceholder = $type === 'rented-dress' ? 'Search dresses...' : ($type === 'rented-jewellery' ? 'Search jewellery...' : 'Search designs...');
    $colorCssMap = [
        'black' => '#111111', 'white' => '#ffffff', 'red' => '#e11d48', 'blue' => '#2563eb',
        'navy' => '#1e3a8a', 'navy blue' => '#1e3a8a', 'green' => '#16a34a', 'yellow' => '#eab308',
        'orange' => '#ea580c', 'pink' => '#ec4899', 'purple' => '#9333ea', 'brown' => '#92400e',
        'grey' => '#6b7280', 'gray' => '#6b7280', 'gold' => '#ca8a04', 'silver' => '#a8a29e',
        'maroon' => '#9f1239', 'ivory' => '#fffff0', 'rose gold' => '#b76e79',
    ];
    $resolveColorCss = function (string $name) use ($colorCssMap): string {
        return $colorCssMap[strtolower(trim($name))] ?? '#94a3b8';
    };
@endphp

<div class="vp-page-head">
    <div>
        <h1 class="vp-page-title">{{ $typeLabel }}</h1>
        <p class="vp-page-sub">Items you want to sell or rent. For previous work photos, use <a href="{{ route('vendor.settings.index', ['tab' => 'portfolio']) }}" style="color:var(--vp-orange);font-weight:600;">Portfolio</a> in Settings.</p>
    </div>
</div>

<div class="vp-card vp-products-card">
    <div class="vp-products-toolbar">
        <form method="GET" action="{{ route('vendor.products.index') }}" class="vp-products-tools" id="vp-products-filter-form">
            <input type="hidden" name="type" value="{{ $type }}">

            <div class="vp-bookings-search">
                <svg class="vp-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
                <input
                    type="search"
                    name="search"
                    value="{{ request('search') }}"
                    class="vp-bookings-search-input"
                    placeholder="{{ $searchPlaceholder }}"
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
                        <a href="{{ route('vendor.products.index', ['type' => $type, 'search' => request('search'), 'status' => request('status'), 'listing' => request('listing')]) }}" class="vp-btn vp-btn--outline vp-btn--sm">Clear</a>
                    </div>
                </div>
            </details>

            @unless ($isRental)
                <label class="vp-sr-only" for="product-approval-filter">Approval</label>
                <select
                    id="product-approval-filter"
                    name="status"
                    class="vp-bookings-status-select"
                    onchange="this.form.submit()"
                >
                    <option value="">Approval</option>
                    @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            @endunless

            <label class="vp-sr-only" for="product-listing-filter">Status</label>
            <select
                id="product-listing-filter"
                name="listing"
                class="vp-bookings-status-select"
                onchange="this.form.submit()"
            >
                <option value="">Status</option>
                <option value="active" @selected(request('listing') === 'active')>Active</option>
                <option value="inactive" @selected(request('listing') === 'inactive')>Inactive</option>
            </select>

            <div class="vp-export-dropdown" x-data="{ open: false }" @click.outside="open = false">
                <button type="button" class="vp-btn vp-btn--outline vp-btn--sm" @click="open = !open" aria-haspopup="true" :aria-expanded="open">
                    <svg class="vp-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                    {{ $isRental ? 'Export All' : 'Export' }}
                    <span aria-hidden="true">▾</span>
                </button>
                <div class="vp-export-menu" x-show="open" x-cloak x-transition>
                    <a href="{{ route('vendor.list-export', array_merge(['module' => 'products', 'format' => 'csv'], request()->only(['type', 'search', 'status', 'listing', 'from', 'to']))) }}" class="vp-export-menu-item">CSV</a>
                    <a href="{{ route('vendor.list-export', array_merge(['module' => 'products', 'format' => 'pdf'], request()->only(['type', 'search', 'status', 'listing', 'from', 'to']))) }}" class="vp-export-menu-item">PDF</a>
                </div>
            </div>
        </form>

        <a href="{{ route('vendor.products.create', ['type' => $type]) }}" class="vp-btn vp-btn--primary">
            @include('vendor.partials.nav-icon', ['icon' => 'plus'])
            Add {{ $addLabel }}
        </a>
    </div>

    <div class="vp-table-wrap">
        <table class="vp-table vp-table--products">
            <thead>
                <tr>
                    <th>Sl. No</th>
                    <th>Name &amp; Image</th>
                    @if ($isRentalDress)
                        <th>Color &amp; Size</th>
                        <th>Price/Day</th>
                    @elseif ($isRental)
                        <th>Price/Day</th>
                    @else
                        <th>Price</th>
                    @endif
                    <th>Rating</th>
                    @unless ($isRental)
                        <th>Approval</th>
                    @endunless
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    @php
                        $isApproved = $item->status === 'approved';
                        $isActive = (bool) ($item->is_listing_active ?? true);
                        $approvalClass = match ($item->status) {
                            'approved' => 'done',
                            'rejected' => 'failed',
                            default => 'pending',
                        };
                        $variants = $isRentalDress ? $item->availableVariants() : collect();
                        $rowColors = $variants->pluck('color')->map(fn ($c) => trim((string) $c))->filter()->unique()->values();
                        $rowSizes = $variants->pluck('size')->map(fn ($s) => trim((string) $s))->filter()->unique()->values();
                    @endphp
                    <tr>
                        <td>{{ $items->firstItem() + $loop->index }}</td>
                        <td>
                            <div class="vp-table-product">
                                @if ($item->displayImageUrl())
                                    <img src="{{ url($item->displayImageUrl()) }}" alt="" class="vp-thumb panel-lightbox-trigger">
                                @else
                                    <span class="vp-thumb"></span>
                                @endif
                                <strong>{{ $item->title }}</strong>
                            </div>
                        </td>
                        @if ($isRentalDress)
                            <td>
                                <div class="vp-table-color-size">
                                    @if ($rowColors->isNotEmpty())
                                        <div class="vp-table-swatches" title="{{ $rowColors->implode(', ') }}">
                                            @foreach ($rowColors->take(4) as $colorName)
                                                @php $css = $resolveColorCss($colorName); @endphp
                                                <span
                                                    class="vp-table-swatch{{ in_array(strtolower($css), ['#ffffff', '#fffff0'], true) ? ' vp-table-swatch--light' : '' }}"
                                                    style="background-color: {{ $css }};"
                                                    aria-label="{{ $colorName }}"
                                                ></span>
                                            @endforeach
                                        </div>
                                    @endif
                                    <div class="vp-table-sizes">
                                        {{ $rowSizes->isNotEmpty() ? $rowSizes->implode(', ') : '—' }}
                                    </div>
                                </div>
                            </td>
                        @endif
                        <td>
                            <strong>₹{{ number_format($item->rentalPriceAmount(), 0) }}</strong>@if ($isRental)<span class="vp-table-meta">/day</span>@endif
                        </td>
                        <td>
                            <span class="vp-rating">
                                <svg class="vp-rating-star" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                {{ number_format($item->vendor?->rating ?? 0, 1) }}
                            </span>
                        </td>
                        @unless ($isRental)
                            <td>
                                <span class="vp-badge vp-badge--{{ $approvalClass }}">{{ strtoupper($item->status) }}</span>
                            </td>
                        @endunless
                        <td>
                            <form
                                method="POST"
                                action="{{ route('vendor.products.listing-active', $item) }}"
                                class="vp-listing-toggle @unless($isApproved) is-disabled @endunless"
                                @unless($isApproved) title="Only approved products can be activated" @endunless
                            >
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="is_listing_active" value="0">
                                <label class="vp-toggle">
                                    <input
                                        type="checkbox"
                                        name="is_listing_active"
                                        value="1"
                                        @checked($isApproved && $isActive)
                                        @disabled(! $isApproved)
                                        onchange="this.form.submit()"
                                    >
                                    <span class="vp-toggle-track"></span>
                                </label>
                                <span @class(['vp-listing-toggle-label', 'is-active' => $isApproved && $isActive])>
                                    {{ $isApproved && $isActive ? 'Active' : 'Inactive' }}
                                </span>
                            </form>
                        </td>
                        <td>
                            <div class="vp-actions vp-actions--products">
                                <a href="{{ route('vendor.products.show', $item) }}" class="vp-btn vp-btn--icon vp-btn--icon-view" title="View" aria-label="View product">
                                    @include('vendor.partials.nav-icon', ['icon' => 'eye'])
                                </a>
                                <a href="{{ route('vendor.products.edit', $item) }}" class="vp-btn vp-btn--icon vp-btn--icon-edit" title="Edit" aria-label="Edit product">
                                    @include('vendor.partials.nav-icon', ['icon' => 'edit'])
                                </a>
                                <form method="POST" action="{{ route('vendor.products.destroy', $item) }}"
                                      data-vp-confirm="This product will be permanently deleted."
                                      data-vp-confirm-title="Delete product?"
                                      data-vp-confirm-label="Delete"
                                      data-vp-confirm-variant="error">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="vp-btn vp-btn--icon vp-btn--icon-delete" title="Delete" aria-label="Delete product">
                                        <svg class="vp-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isRentalDress ? 7 : ($isRental ? 6 : 7) }}">
                            <div class="vp-empty-state">
                                <p class="vp-empty-state__title">No products yet</p>
                                <p class="vp-empty-state__text">
                                    <a href="{{ route('vendor.products.create', ['type' => $type]) }}">Add your first {{ strtolower($type === 'rented-dress' ? 'dress' : $addLabel) }}</a>.
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($items->hasPages())
        <div class="vp-card-pad">{{ $items->links('vendor.pagination.default') }}</div>
    @endif
</div>
@endsection
