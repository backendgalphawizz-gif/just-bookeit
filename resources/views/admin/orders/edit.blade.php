@extends('admin.layouts.app')
@section('title', 'Edit Order')
@section('page_title', $order->order_number)
@section('back_href', route('admin.orders.index'))
@section('content')
    <div class="jb-card"><div class="jb-card-body">
        <form method="POST" action="{{ route('admin.orders.update', $order) }}" enctype="multipart/form-data">@csrf @method('PUT')
            <div class="jb-form-grid">@include('admin.orders._form', compact('order', 'customers', 'vendors', 'drivers', 'categories'))</div>
            <div class="jb-form-actions"><x-admin.button variant="primary" type="submit">Update Order</x-admin.button><x-admin.button variant="secondary" :href="route('admin.orders.index')">Cancel</x-admin.button></div>
        </form>
    </div></div>
@endsection
