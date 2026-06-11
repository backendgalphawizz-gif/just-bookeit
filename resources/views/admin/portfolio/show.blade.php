@extends('admin.layouts.app')
@section('title', $portfolio->title)
@section('page_title', $portfolio->title)
@section('page_subtitle', $portfolio->vendor->brand_name)

@section('back_href', route('admin.portfolio.index'))
@section('header_actions')
    @if (auth('admin')->user()->hasPermission('portfolio', 'edit'))
        <x-admin.action-btn variant="edit" :href="route('admin.portfolio.edit', $portfolio)" />
    @endif
    @if (in_array($portfolio->status, ['pending', 'rejected'], true) && auth('admin')->user()->hasPermission('portfolio', 'edit'))
        <form method="POST" action="{{ route('admin.portfolio.approve', $portfolio) }}" class="inline-flex">@csrf
            <x-admin.action-btn variant="approve" type="submit" title="{{ $portfolio->status === 'rejected' ? 'Approve again' : 'Approve' }}" />
        </form>
    @endif
@endsection

@section('content')
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="jb-detail-card">
            <dl class="jb-detail-list">
                <div><dt>Vendor</dt><dd>{{ $portfolio->vendor->brand_name }}</dd></div>
                <div><dt>Category</dt><dd>{{ $portfolio->category?->name ?? '—' }}</dd></div>
                <div><dt>Audience</dt><dd>{{ ucfirst($portfolio->audience ?? 'women') }}</dd></div>
                <div><dt>Status</dt><dd>@include('admin.components.status-badge', ['status' => $portfolio->status])</dd></div>
                <div><dt>Photos</dt><dd>{{ count($portfolio->galleryImageUrls()) }}</dd></div>
                <div><dt>Submitted</dt><dd>{{ $portfolio->created_at->format('M d, Y h:i A') }}</dd></div>
                @if ($portfolio->reviewed_at)
                    <div><dt>Reviewed</dt><dd>{{ $portfolio->reviewed_at->format('M d, Y h:i A') }}</dd></div>
                @endif
                @if ($portfolio->description)
                    <div class="sm:col-span-2"><dt>Description</dt><dd>{{ $portfolio->description }}</dd></div>
                @endif
                @if ($portfolio->rejection_reason)
                    <div class="sm:col-span-2"><dt>Rejection reason</dt><dd class="text-rose-700">{{ $portfolio->rejection_reason }}</dd></div>
                @endif
            </dl>
        </div>

        @if (count($portfolio->galleryImageUrls()) > 0)
            <div class="jb-card overflow-hidden">
                <div class="jb-card-header">
                    <p class="jb-card-header-title">Photos</p>
                    @if (auth('admin')->user()->hasPermission('portfolio', 'edit'))
                        <x-admin.button variant="secondary" size="sm" :href="route('admin.portfolio.edit', $portfolio)">Manage photos</x-admin.button>
                    @endif
                </div>
                <div class="jb-card-body grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach ($portfolio->galleryImageUrls() as $url)
                        <img src="{{ $url }}" alt="{{ $portfolio->title }}" class="aspect-square w-full rounded-xl object-cover ring-1 ring-slate-200 panel-lightbox-trigger">
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    @if (in_array($portfolio->status, ['pending', 'rejected'], true) && auth('admin')->user()->hasPermission('portfolio', 'edit'))
        <div class="jb-card mt-6 max-w-2xl">
            <div class="jb-card-header"><p class="jb-card-header-title">Moderation</p></div>
            <div class="jb-card-body space-y-4">
                @if ($portfolio->status === 'rejected')
                    <p class="text-sm text-rose-700">This item was rejected. Approve it again if the vendor has fixed the issues.</p>
                @endif
                <div class="flex flex-wrap gap-3">
                    <form method="POST" action="{{ route('admin.portfolio.approve', $portfolio) }}">@csrf
                        <x-admin.button variant="primary" type="submit">{{ $portfolio->status === 'rejected' ? 'Approve again' : 'Approve' }}</x-admin.button>
                    </form>
                    @if ($portfolio->status === 'pending')
                        <form method="POST" action="{{ route('admin.portfolio.reject', $portfolio) }}" class="flex-1 min-w-[16rem] space-y-3">
                            @csrf
                            @include('admin.partials.form-input', ['label' => 'Rejection reason', 'name' => 'rejection_reason', 'type' => 'textarea', 'value' => old('rejection_reason'), 'required' => true, 'full' => true])
                            <x-admin.button variant="danger" type="submit">Reject</x-admin.button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endif
@endsection
