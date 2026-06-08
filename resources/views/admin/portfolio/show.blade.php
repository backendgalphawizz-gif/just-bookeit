@extends('admin.layouts.app')
@section('title', $portfolio->title)
@section('page_title', $portfolio->title)
@section('page_subtitle', $portfolio->vendor->brand_name)

@section('back_href', route('admin.portfolio.index'))
@section('header_actions')
    @if ($portfolio->status === 'pending' && auth('admin')->user()->hasPermission('portfolio', 'edit'))
        <form method="POST" action="{{ route('admin.portfolio.approve', $portfolio) }}" class="inline-flex">@csrf
            <x-admin.action-btn variant="approve" type="submit" />
        </form>
    @endif
@endsection

@section('content')
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="jb-detail-card">
            <dl class="jb-detail-list">
                <div><dt>Vendor</dt><dd>{{ $portfolio->vendor->brand_name }}</dd></div>
                <div><dt>Category</dt><dd>{{ $portfolio->category?->name ?? '—' }}</dd></div>
                <div><dt>Status</dt><dd>@include('admin.components.status-badge', ['status' => $portfolio->status])</dd></div>
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
        @if ($portfolio->image_url)
            <div class="jb-card overflow-hidden">
                <img src="{{ $portfolio->image_url }}" alt="{{ $portfolio->title }}" class="h-full w-full object-cover min-h-[16rem] panel-lightbox-trigger">
            </div>
        @endif
    </div>
    @if ($portfolio->status === 'pending' && auth('admin')->user()->hasPermission('portfolio', 'edit'))
        <div class="jb-card mt-6 max-w-2xl">
            <div class="jb-card-header"><p class="jb-card-header-title">Moderation</p></div>
            <div class="jb-card-body flex flex-wrap gap-3">
                <form method="POST" action="{{ route('admin.portfolio.approve', $portfolio) }}">@csrf
                    <x-admin.button variant="primary" type="submit">Approve</x-admin.button>
                </form>
                <form method="POST" action="{{ route('admin.portfolio.reject', $portfolio) }}" class="flex-1 min-w-[16rem] space-y-3">
                    @csrf
                    @include('admin.partials.form-input', ['label' => 'Rejection reason', 'name' => 'rejection_reason', 'type' => 'textarea', 'value' => old('rejection_reason'), 'required' => true, 'full' => true])
                    <x-admin.button variant="danger" type="submit">Reject</x-admin.button>
                </form>
            </div>
        </div>
    @endif
@endsection
