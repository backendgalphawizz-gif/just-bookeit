@extends('admin.layouts.app')

@php
    $galleryUrls = $portfolio->galleryImageUrls();
    $photoCount = count($galleryUrls);
@endphp

@section('title', $portfolio->title)
@section('page_title')
    <span class="block max-w-full truncate" title="{{ $portfolio->title }}">{{ $portfolio->title }}</span>
@endsection
@section('page_subtitle', 'Product · '.$portfolio->vendor->brand_name)

@section('back_href', route('admin.portfolio.index'))
@section('header_actions')
    @if (auth('admin')->user()->hasPermission('portfolio', 'edit'))
        <x-admin.button variant="secondary" :href="route('admin.portfolio.edit', $portfolio)">Edit product</x-admin.button>
    @endif
    @if (in_array($portfolio->status, ['pending', 'rejected'], true) && auth('admin')->user()->hasPermission('portfolio', 'edit'))
        <form method="POST" action="{{ route('admin.portfolio.approve', $portfolio) }}" class="inline-flex">@csrf
            <x-admin.button variant="success" type="submit">{{ $portfolio->status === 'rejected' ? 'Approve again' : 'Approve' }}</x-admin.button>
        </form>
    @endif
@endsection

@section('content')
    <div class="jb-product-hero">
        <div class="jb-product-hero-cover @unless($portfolio->displayImageUrl()) jb-product-hero-cover--empty @endunless">
            @if ($portfolio->displayImageUrl())
                <img src="{{ $portfolio->displayImageUrl() }}" alt="{{ $portfolio->title }}" class="panel-lightbox-trigger">
            @else
                <svg class="size-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z" />
                </svg>
            @endif
        </div>

        <div class="jb-product-hero-body">
            <div class="jb-product-hero-badges">
                @include('admin.components.status-badge', ['status' => $portfolio->status])
                @if ($portfolio->category?->name)
                    <span class="jb-product-type-pill">{{ $portfolio->category->name }}</span>
                @endif
                <span class="jb-product-type-pill">{{ ucfirst($portfolio->audience ?? 'women') }}</span>
            </div>

            <p class="jb-product-hero-price">
                @if ($portfolio->price_per_day !== null)
                    ₹{{ number_format((float) $portfolio->price_per_day, 0) }}
                    <span>/ day</span>
                @else
                    <span>Price not set</span>
                @endif
            </p>

            <p class="jb-product-hero-vendor">
                Sold by
                <a href="{{ route('admin.vendors.show', $portfolio->vendor) }}">{{ $portfolio->vendor->brand_name }}</a>
                @if ($portfolio->vendor->city)
                    · {{ $portfolio->vendor->city }}
                @endif
            </p>

            @if ($portfolio->description)
                <p class="jb-product-hero-desc">{{ $portfolio->description }}</p>
            @endif

            @if ($portfolio->rejection_reason)
                <div class="jb-product-reject-box">{{ $portfolio->rejection_reason }}</div>
            @endif
        </div>
    </div>

    <div class="jb-product-layout">
        <div class="jb-product-main">
            @if ($photoCount > 0)
                <div class="jb-booking-card">
                    <div class="jb-booking-card-head">
                        <h3 class="jb-booking-card-title">Photos ({{ $photoCount }})</h3>
                    </div>
                    <div class="jb-product-gallery">
                        @foreach ($galleryUrls as $url)
                            <div class="jb-product-gallery-item">
                                <img src="{{ $url }}" alt="{{ $portfolio->title }}" class="panel-lightbox-trigger">
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($portfolio->variants->isNotEmpty())
                <div class="jb-booking-card">
                    <h3 class="jb-booking-card-title">Size / color variants</h3>
                    <div class="jb-table-wrap">
                        <table class="jb-table jb-table--balanced">
                            <thead>
                                <tr>
                                    <th>Size</th>
                                    <th>Color</th>
                                    <th class="jb-col-amount">Price (₹)</th>
                                    <th>Image</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($portfolio->variants as $variant)
                                    <tr>
                                        <td>{{ $variant->size ?: '—' }}</td>
                                        <td>{{ $variant->color ?: '—' }}</td>
                                        <td class="jb-col-amount">{{ number_format((float) $variant->price, 2) }}</td>
                                        <td>
                                            @if ($variant->imageUrl())
                                                <img src="{{ $variant->imageUrl() }}" alt="" class="h-10 w-10 rounded-lg object-cover ring-1 ring-slate-200 panel-lightbox-trigger">
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if ($portfolio->damageDeductions->isNotEmpty())
                <div class="jb-booking-card">
                    <h3 class="jb-booking-card-title">Damage deduction rules</h3>
                    <div class="jb-table-wrap">
                        <table class="jb-table jb-table--balanced">
                            <thead>
                                <tr>
                                    <th>Damage type</th>
                                    <th class="jb-col-amount">Deduction (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($portfolio->damageDeductions as $rule)
                                    <tr>
                                        <td>{{ $rule->damage_type }}</td>
                                        <td class="jb-col-amount">{{ number_format((float) $rule->percent, 2) }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <aside class="jb-product-sidebar">
            <div class="jb-booking-card jb-booking-card--compact">
                <h3 class="jb-booking-card-title">Product details</h3>
                <dl class="jb-product-facts">
                    <div class="jb-product-fact">
                        <dt>Vendor</dt>
                        <dd>
                            <a href="{{ route('admin.vendors.show', $portfolio->vendor) }}" class="jb-booking-link">{{ $portfolio->vendor->brand_name }}</a>
                        </dd>
                    </div>
                    <div class="jb-product-fact">
                        <dt>Product type</dt>
                        <dd>{{ $portfolio->category?->name ?? '—' }}</dd>
                    </div>
                    <div class="jb-product-fact">
                        <dt>Category</dt>
                        <dd>{{ $portfolio->subcategory?->parent?->name ?? '—' }}</dd>
                    </div>
                    <div class="jb-product-fact">
                        <dt>Sub-category</dt>
                        <dd>{{ $portfolio->subcategory?->name ?? '—' }}</dd>
                    </div>
                    <div class="jb-product-fact">
                        <dt>Audience</dt>
                        <dd>{{ ucfirst($portfolio->audience ?? 'women') }}</dd>
                    </div>
                    <div class="jb-product-fact">
                        <dt>Price per day</dt>
                        <dd>{{ $portfolio->price_per_day !== null ? '₹'.number_format((float) $portfolio->price_per_day, 2) : '—' }}</dd>
                    </div>
                    <div class="jb-product-fact">
                        <dt>Advance amount</dt>
                        <dd>{{ $portfolio->advance_amount !== null ? '₹'.number_format((float) $portfolio->advance_amount, 2) : '—' }}</dd>
                    </div>
                    <div class="jb-product-fact">
                        <dt>Status</dt>
                        <dd>@include('admin.components.status-badge', ['status' => $portfolio->status])</dd>
                    </div>
                    <div class="jb-product-fact">
                        <dt>Total photos</dt>
                        <dd>{{ $photoCount }}</dd>
                    </div>
                    <div class="jb-product-fact">
                        <dt>Submitted</dt>
                        <dd>{{ $portfolio->created_at->format('M d, Y · h:i A') }}</dd>
                    </div>
                    @if ($portfolio->reviewed_at)
                        <div class="jb-product-fact">
                            <dt>Reviewed</dt>
                            <dd>{{ $portfolio->reviewed_at->format('M d, Y · h:i A') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            @if (in_array($portfolio->status, ['pending', 'rejected'], true) && auth('admin')->user()->hasPermission('portfolio', 'edit'))
                <div class="jb-booking-card">
                    <h3 class="jb-booking-card-title">Moderation</h3>
                    @if ($portfolio->status === 'rejected')
                        <p class="text-sm text-rose-700" style="margin:0 0 .85rem;">This product was rejected. Approve again if the vendor has fixed the issues.</p>
                    @endif
                    <div class="flex flex-col gap-3">
                        <form method="POST" action="{{ route('admin.portfolio.approve', $portfolio) }}">@csrf
                            <x-admin.button variant="primary" type="submit" class="w-full">{{ $portfolio->status === 'rejected' ? 'Approve again' : 'Approve product' }}</x-admin.button>
                        </form>
                        @if ($portfolio->status === 'pending')
                            <form method="POST" action="{{ route('admin.portfolio.reject', $portfolio) }}" class="space-y-3">
                                @csrf
                                @include('admin.partials.form-input', ['label' => 'Rejection reason', 'name' => 'rejection_reason', 'type' => 'textarea', 'value' => old('rejection_reason'), 'required' => true, 'full' => true])
                                <x-admin.button variant="danger" type="submit" class="w-full">Reject product</x-admin.button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif
        </aside>
    </div>
@endsection
