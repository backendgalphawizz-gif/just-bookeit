@extends('admin.layouts.app')
@section('title', 'New Refund')
@section('page_title', 'New Refund')
@section('back_href', route('admin.refunds.index'))
@section('content')
    <div class="jb-card"><div class="jb-card-body">
        <form method="POST" action="{{ route('admin.refunds.store') }}">@csrf
            <div class="jb-form-grid">@include('admin.refunds._form')</div>
            <div class="jb-form-actions"><x-admin.button variant="primary" type="submit">Save</x-admin.button><x-admin.button variant="secondary" :href="route('admin.refunds.index')">Cancel</x-admin.button></div>
        </form>
    </div></div>
@endsection
