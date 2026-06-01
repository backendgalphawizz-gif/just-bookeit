@extends('web.layouts.profile')

@section('title', 'Addresses')
@section('page_title', 'Address')
@section('page_subtitle', 'View and manage your addresses.')

@section('content')
    <div class="jbw-address-grid">
        <div class="jbw-card jbw-address-card">
            <span class="jbw-address-tag">HOME</span>
            <p style="margin:0;line-height:1.6;color:var(--jbw-muted)">{{ $customer->name }}<br>{{ $customer->city ?? 'Add your city in profile' }}<br>India</p>
        </div>
        <a href="#" class="jbw-add-card">
            <div style="text-align:center">
                <span style="font-size:2rem;color:var(--jbw-primary)">+</span>
                <strong>Add new address</strong>
            </div>
        </a>
    </div>
@endsection
