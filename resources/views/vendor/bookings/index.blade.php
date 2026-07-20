@extends('vendor.layouts.app')

@section('title', 'Bookings')

@section('content')
<div class="vp-page-head vp-page-head--bookings">
    <div>
        <h1 class="vp-page-title">All Bookings</h1>
    </div>
</div>

@include('vendor.bookings.partials.figma-list', [
    'orders' => $orders,
    'listRoute' => route('vendor.bookings.index'),
    'showPagination' => true,
    'embedOnDashboard' => false,
])
@endsection
