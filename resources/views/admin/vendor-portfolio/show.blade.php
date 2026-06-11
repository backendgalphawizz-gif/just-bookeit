@extends('admin.layouts.app')

@section('title', $vendor->brand_name.' — Portfolio')
@section('page_title', $vendor->brand_name)
@section('page_subtitle', 'Portfolio · previous work photos')
@section('back_href', route('admin.vendor-portfolio.index', request()->only(['search', 'vendor_id', 'status', 'city', 'audience', 'from', 'to'])))

@section('header_actions')
    <x-admin.button variant="secondary" size="sm" :href="route('admin.vendors.show', $vendor)">Vendor profile</x-admin.button>
@endsection

@section('content')
    <div class="jb-detail-card mb-6">
        <dl class="jb-detail-list">
            <div><dt>Owner</dt><dd>{{ $vendor->owner_name ?? '—' }}</dd></div>
            <div><dt>City</dt><dd>{{ $vendor->city ?? '—' }}</dd></div>
            <div><dt>Mobile</dt><dd>{{ $vendor->mobile ?? $vendor->business_mobile ?? '—' }}</dd></div>
            <div><dt>Status</dt><dd>@include('admin.components.status-badge', ['status' => $vendor->status])</dd></div>
            <div><dt>Portfolio photos</dt><dd>{{ $photoCount }}</dd></div>
        </dl>
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
            <p class="jb-card-header-title">{{ $photoCount }} {{ Str::plural('photo', $photoCount) }}</p>
        </div>
        <div class="jb-card-body space-y-8">
            @if ($photoCount > 0)
                @foreach ($portfolioByAudience as $audienceKey => $group)
                    @if ($group['images']->isEmpty())
                        @continue
                    @endif

                    <section id="audience-{{ $audienceKey }}">
                        <div class="mb-4">
                            <h2 class="text-base font-semibold text-slate-900">{{ $group['label'] }}</h2>
                            <p class="mt-0.5 text-sm text-slate-500">{{ $group['images']->count() }} {{ Str::plural('photo', $group['images']->count()) }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
                            @foreach ($group['images'] as $image)
                                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                                    @if ($url = $image->imageUrl())
                                        <img src="{{ $url }}" alt="Portfolio work — {{ $group['label'] }}" class="aspect-square w-full object-cover panel-lightbox-trigger">
                                    @else
                                        <div class="flex aspect-square items-center justify-center bg-slate-100 text-xs text-slate-400">No image</div>
                                    @endif
                                    <div class="p-3 text-xs text-slate-500">
                                        {{ $image->created_at->format('M d, Y') }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            @else
                <p class="jb-table-empty py-8 text-center">No portfolio photos match your filters for this vendor.</p>
            @endif
        </div>
    </div>
@endsection
