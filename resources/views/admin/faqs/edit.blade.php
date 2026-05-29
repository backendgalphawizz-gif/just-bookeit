@extends('admin.layouts.app')
@section('title', 'Edit FAQ')
@section('page_title', 'Edit FAQ')
@section('page_subtitle', \App\Models\Faq::audienceLabel($faq->audience))
@section('header_actions')
    <x-admin.button variant="secondary" :href="route('admin.faqs.index', ['audience' => $faq->audience])">← Back</x-admin.button>
@endsection
@section('content')
    <div class="jb-card max-w-3xl">
        <div class="jb-card-body">
            <form method="POST" action="{{ route('admin.faqs.update', $faq) }}">
                @csrf
                @method('PUT')
                <div class="jb-form-grid">@include('admin.faqs._form')</div>
                <div class="jb-form-actions">
                    <x-admin.button variant="primary" type="submit">Update FAQ</x-admin.button>
                    <x-admin.button variant="secondary" :href="route('admin.faqs.index', ['audience' => $faq->audience])">Cancel</x-admin.button>
                </div>
            </form>
        </div>
    </div>
@endsection
