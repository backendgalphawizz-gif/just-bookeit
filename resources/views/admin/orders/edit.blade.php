@extends('admin.layouts.app')
@section('title', 'Edit Order')
@section('page_title', $order->order_number)
@section('header_actions')<x-admin.button variant="secondary" :href="route('admin.orders.show', $order)">← Back</x-admin.button>@endsection
@section('content')
    <div class="jb-card max-w-4xl"><div class="jb-card-body">
        <form method="POST" action="{{ route('admin.orders.update', $order) }}">@csrf @method('PUT')
            <div class="jb-form-grid">@include('admin.orders._form')</div>
            <div class="jb-form-actions"><x-admin.button variant="primary" type="submit">Update Order</x-admin.button><x-admin.button variant="secondary" :href="route('admin.orders.show', $order)">Cancel</x-admin.button></div>
        </form>
    </div></div>
@endsection
