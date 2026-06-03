@extends('admin.layouts.app')
@section('title', 'Edit Refund')
@section('page_title', 'Edit Refund #'.$refund->id)
@section('back_href', route('admin.refunds.index'))
@section('content')
    <div class="jb-card"><div class="jb-card-body">
        <form method="POST" action="{{ route('admin.refunds.update', $refund) }}">@csrf @method('PUT')
            <div class="jb-form-grid">@include('admin.refunds._form')</div>
            <div class="jb-form-actions"><x-admin.button variant="primary" type="submit">Update</x-admin.button><x-admin.button variant="secondary" :href="route('admin.refunds.index')">Cancel</x-admin.button></div>
        </form>
    </div></div>
@endsection
