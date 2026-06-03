@extends('admin.layouts.app')
@section('title', 'Edit Dispute')
@section('page_title', 'Edit Dispute')
@section('back_href', route('admin.disputes.index'))
@section('content')
    <div class="jb-card"><div class="jb-card-body">
        <form method="POST" action="{{ route('admin.disputes.update', $dispute) }}">@csrf @method('PUT')
            <div class="jb-form-grid">@include('admin.disputes._form')</div>
            <div class="jb-form-actions"><x-admin.button variant="primary" type="submit">Update</x-admin.button><x-admin.button variant="secondary" :href="route('admin.disputes.index')">Cancel</x-admin.button></div>
        </form>
    </div></div>
@endsection
