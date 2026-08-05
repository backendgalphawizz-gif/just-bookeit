@extends('admin.layouts.app')

@section('title', $vendor->brand_name.' — Portfolio')
@section('page_title')
    <span class="block max-w-full truncate" title="{{ $vendor->brand_name }}">{{ $vendor->brand_name }}</span>
@endsection
@section('page_subtitle', $vendor->vendor_code.' · Product photos')
@section('back_href', route('admin.vendor-portfolio.index', request()->only(['search', 'vendor_id', 'status', 'city', 'audience', 'from', 'to'])))

@section('header_actions')
    <x-admin.button variant="secondary" size="sm" :href="route('admin.vendors.show', $vendor)">Vendor profile</x-admin.button>
@endsection

@section('content')
    @php
        $audienceCounts = collect($portfolioByAudience)->mapWithKeys(fn (array $group, string $key) => [$key => $group['images']->count()]);
        $filtersActive = request()->filled('audience') || request()->filled('from') || request()->filled('to');
    @endphp

    <div class=" mb-6 jb-card">
        <div class="jb-detail-card lg:col-span-2">
            <h2>Vendor details</h2>
            <x-admin.actor-profile-header
                :image-url="$vendor->profileImageUrl()"
                :fallback-url="$vendor->shopLogoUrl()"
                :title="$vendor->shop_name ?? $vendor->brand_name"
                :subtitle="$vendor->vendor_code"
            >
                @include('admin.components.status-badge', ['status' => $vendor->status, 'label' => \App\Support\AdminAccountStatus::labelFor($vendor->status)])
            </x-admin.actor-profile-header>

            <dl class="jb-dl jb-dl--grid">
                <div>
                    <dt>Brand name</dt>
                    <dd>{{ $vendor->brand_name ?? '—' }}</dd>
                </div>
                <div>
                    <dt>Owner</dt>
                    <dd>{{ $vendor->owner_name ?? '—' }}</dd>
                </div>
                <div>
                    <dt>Mobile no</dt>
                    <dd>{{ $vendor->mobile ?? '—' }}</dd>
                </div>
                <div>
                    <dt>Business mobile</dt>
                    <dd>{{ $vendor->business_mobile ?? '—' }}</dd>
                </div>
                <div>
                    <dt>Email ID</dt>
                    <dd>{{ $vendor->email ?? '—' }}</dd>
                </div>
                <div>
                    <dt>City</dt>
                    <dd>{{ $vendor->city ?? '—' }}</dd>
                </div>
                <div>
                    <dt>Product photos</dt>
                    <dd>
                        @if ($filtersActive && $photoCount !== $totalPhotoCount)
                            {{ $photoCount }} shown · {{ $totalPhotoCount }} total
                        @else
                            {{ $totalPhotoCount }} {{ Str::plural('photo', $totalPhotoCount) }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt>By audience</dt>
                    <dd class="flex flex-wrap gap-2">
                        @foreach ($portfolioByAudience as $key => $group)
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                {{ $group['label'] }}: {{ $audienceCounts[$key] ?? 0 }}
                            </span>
                        @endforeach
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <form method="GET" class="jb-filters mb-6">
        <div class="jb-filters-grid">
            <div class="jb-filters-field">
                <label class="jb-label">Audience</label>
                <select name="audience" class="jb-select">
                    <option value="">All</option>
                    @foreach (['women' => 'Women', 'men' => 'Men', 'kids' => 'Kids'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('audience') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @include('admin.partials.date-filter')
            @include('admin.partials.filters-end', ['resetUrl' => route('admin.vendor-portfolio.show', $vendor)])
        </div>
    </form>

    <div class="jb-card">
        <div class="jb-card-header">
            <p class="jb-card-header-title">
                @if ($filtersActive && $photoCount !== $totalPhotoCount)
                    {{ $photoCount }} of {{ $totalPhotoCount }} {{ Str::plural('photo', $totalPhotoCount) }}
                @else
                    {{ $photoCount }} {{ Str::plural('photo', $photoCount) }}
                @endif
                <span class="font-normal text-slate-500">from products</span>
            </p>
        </div>
        <div class="jb-card-body">
            @if ($photoCount > 0)
                <div class="space-y-10">
                    @foreach ($portfolioByAudience as $audienceKey => $group)
                        @if ($group['images']->isEmpty())
                            @continue
                        @endif

                        <section id="audience-{{ $audienceKey }}">
                            <div class="mb-4 mt-3 flex flex-wrap items-end justify-between gap-3 border-b border-slate-100 pb-3">
                                <div>
                                    <h2 class="text-base font-bold text-slate-900">{{ $group['label'] }}</h2>
                                    <p class="mt-0.5 mn text-sm text-slate-500">{{ $group['images']->count() }} {{ Str::plural('photo', $group['images']->count()) }}</p>
                                </div>
                            </div>

                            <div class="jb-vendor-product-photo-grid">
                                @foreach ($group['images'] as $image)
                                    <article class="jb-vendor-product-photo-card">
                                        @if (! empty($image['url']))
                                            <a href="{{ route('admin.portfolio.show', $image['product_id']) }}" class="jb-vendor-product-photo-card__media">
                                                <img
                                                    src="{{ $image['url'] }}"
                                                    alt="{{ $image['title'] }}"
                                                >
                                            </a>
                                        @else
                                            <div class="jb-vendor-product-photo-card__media jb-vendor-product-photo-card__media--empty">No image</div>
                                        @endif
                                        <div class="jb-vendor-product-photo-card__body">
                                            <p class="jb-vendor-product-photo-card__title" title="{{ $image['title'] }}">{{ $image['title'] }}</p>
                                            <p class="jb-vendor-product-photo-card__meta">
                                                {{ $group['label'] }}
                                                @if (! empty($image['created_at']))
                                                    · {{ \App\Support\AdminDateTime::formatDate($image['created_at']) }}
                                                @endif
                                            </p>
                                            <a href="{{ route('admin.portfolio.show', $image['product_id']) }}" class="jb-vendor-product-photo-card__link">View product</a>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
            @else
                <div class="py-12 text-center">
                    <p class="text-sm font-medium text-slate-700">No product photos match your filters.</p>
                    @if ($filtersActive && $totalPhotoCount > 0)
                        <p class="mt-1 text-sm text-slate-500">This vendor has {{ $totalPhotoCount }} product {{ Str::plural('photo', $totalPhotoCount) }} in total — try clearing the filters.</p>
                        <div class="mt-4">
                            <x-admin.button variant="secondary" size="sm" :href="route('admin.vendor-portfolio.show', $vendor)">Clear filters</x-admin.button>
                        </div>
                    @else
                        <p class="mt-1 text-sm text-slate-500">This vendor has not added images to any products yet.</p>
                        <div class="mt-4">
                            <x-admin.button variant="secondary" size="sm" :href="route('admin.portfolio.index', ['vendor_id' => $vendor->id])">View products</x-admin.button>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection
