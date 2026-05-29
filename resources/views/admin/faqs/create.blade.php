@extends('admin.layouts.app')
@section('title', 'Add FAQ')
@section('page_title', 'Add FAQ')
@section('page_subtitle', \App\Models\Faq::audienceLabel($audience))
@section('header_actions')
    <x-admin.button variant="secondary" :href="route('admin.faqs.index', ['audience' => $audience])">← Back</x-admin.button>
@endsection
@section('content')
    <div class="jb-card max-w-3xl">
        <div class="jb-card-body">
            <form method="POST" action="{{ route('admin.faqs.store') }}">
                @csrf
                <input type="hidden" name="audience" value="{{ $audience }}">
                <div class="jb-form-grid">@include('admin.faqs._form')</div>
                <div class="jb-form-actions">
                    <x-admin.button variant="primary" type="submit">Save FAQ</x-admin.button>
                    <x-admin.button variant="secondary" :href="route('admin.faqs.index', ['audience' => $audience])">Cancel</x-admin.button>
                </div>
            </form>
        </div>
    </div>
@endsection
