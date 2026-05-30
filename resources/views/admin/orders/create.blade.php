@extends('admin.layouts.app')
@section('title', 'New Order')
@section('page_title', 'New Order')
@section('header_actions')<x-admin.button variant="secondary" :href="route('admin.orders.index')">← Back</x-admin.button>@endsection
@section('content')
    <div class="jb-card max-w-4xl"><div class="jb-card-body">
        <form method="POST" action="{{ route('admin.orders.store') }}">@csrf
            <div class="jb-form-grid">@include('admin.orders._form', ['order' => null, 'customers' => $customers, 'vendors' => $vendors, 'drivers' => $drivers, 'categories' => $categories])</div>
            <div class="jb-form-actions"><x-admin.button variant="primary" type="submit">Create Order</x-admin.button><x-admin.button variant="secondary" :href="route('admin.orders.index')">Cancel</x-admin.button></div>
        </form>
    </div></div>
@endsection
