@extends('admin.layouts.app')
@section('title', 'Edit Dispute')
@section('page_title', 'Edit Dispute')
@section('header_actions')<x-admin.button variant="secondary" :href="route('admin.disputes.show', $dispute)">← Back</x-admin.button>@endsection
@section('content')
    <div class="jb-card"><div class="jb-card-body">
        <form method="POST" action="{{ route('admin.disputes.update', $dispute) }}">@csrf @method('PUT')
            <div class="jb-form-grid">@include('admin.disputes._form')</div>
            <div class="jb-form-actions"><x-admin.button variant="primary" type="submit">Update</x-admin.button><x-admin.button variant="secondary" :href="route('admin.disputes.show', $dispute)">Cancel</x-admin.button></div>
        </form>
    </div></div>
@endsection
