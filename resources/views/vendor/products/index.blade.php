@extends('vendor.layouts.app')

@section('title', $typeLabel)

@section('content')
@push('filter_actions')
    <a href="{{ route('vendor.products.create', ['type' => $type]) }}" class="vp-btn vp-btn--primary vp-btn--sm">
        @include('vendor.partials.nav-icon', ['icon' => 'plus'])
        Add {{ $type === 'rented-dress' ? 'Dress' : ($type === 'rented-jewellery' ? 'Jewellery' : 'Design') }}
    </a>
@endpush

<div class="vp-page-head">
    <div>
        <h1 class="vp-page-title">{{ $typeLabel }}</h1>
        <p class="vp-page-sub">Items you want to sell or rent. For previous work photos, use <a href="{{ route('vendor.settings.index', ['tab' => 'portfolio']) }}" style="color:var(--vp-orange);font-weight:600;">Portfolio</a> in Settings.</p>
    </div>
</div>

@push('filter_actions')
    <x-vendor.export-dropdown module="products" :params="['type', 'search', 'status', 'from', 'to']" />
@endpush

<form method="GET" class="vp-filters vp-card" style="padding: 1rem;">
    <input type="hidden" name="type" value="{{ $type }}">
    <div class="vp-filters-grid">
        <div class="vp-filters-field vp-filters-field--wide">
            <label class="vp-label" for="product-search">Search</label>
            <input type="text" id="product-search" name="search" value="{{ request('search') }}" class="vp-input" placeholder="Product name...">
        </div>
        <div class="vp-filters-field">
            <label class="vp-label" for="product-status">Status</label>
            <select id="product-status" name="status" class="vp-select">
                <option value="">All</option>
                @foreach (['pending','approved','rejected'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        @include('vendor.partials.date-filter')
        @include('vendor.partials.filters-end', ['resetUrl' => route('vendor.products.index', ['type' => $type])])
    </div>
</form>

<div class="vp-card" style="margin-top: 1rem;">
    <div class="vp-card-count">{{ $items->total() }} products</div>
    <div class="vp-table-wrap">
        <table class="vp-table">
            <thead>
                <tr>
                    <th>Sl.</th>
                    <th>Name &amp; Image</th>
                    <th>Price</th>
                    <th>Rating</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
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
                        <td>₹{{ number_format($item->rentalPriceAmount(), 0) }}{{ $type !== 'fashion-designer' ? '/day' : '' }}</td>
                        <td>⭐ {{ number_format($item->vendor?->rating ?? 0, 1) }}</td>
                        <td>
                            <span class="vp-badge vp-badge--{{ $item->status === 'approved' ? 'done' : ($item->status === 'rejected' ? 'failed' : 'pending') }}">{{ ucfirst($item->status) }}</span>
                        </td>
                        <td>
                            <div class="vp-actions">
                                <a href="{{ route('vendor.products.edit', $item) }}" class="vp-btn vp-btn--outline vp-btn--sm">Edit</a>
                                <form method="POST" action="{{ route('vendor.products.destroy', $item) }}"
                                      data-vp-confirm="This product will be permanently deleted."
                                      data-vp-confirm-title="Delete product?"
                                      data-vp-confirm-label="Delete"
                                      data-vp-confirm-variant="error">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="vp-btn vp-btn--danger vp-btn--sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="vp-empty">No products yet. Add your first item.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($items->hasPages())
        <div class="vp-card-pad">{{ $items->links('vendor.pagination.default') }}</div>
    @endif
</div>
@endsection
