@extends('admin.layouts.app')
@section('title', 'New Dispute')
@section('page_title', 'New Dispute')
@section('header_actions')<x-admin.button variant="secondary" :href="route('admin.disputes.index')">← Back</x-admin.button>@endsection
@section('content')
    <div class="jb-card"><div class="jb-card-body">
        <form method="POST" action="{{ route('admin.disputes.store') }}">@csrf
            <div class="jb-form-grid">@include('admin.disputes._form')</div>
            <div class="jb-form-actions"><x-admin.button variant="primary" type="submit">Save</x-admin.button><x-admin.button variant="secondary" :href="route('admin.disputes.index')">Cancel</x-admin.button></div>
        </form>
    </div></div>
@endsection
