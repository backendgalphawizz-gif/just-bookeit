@extends('vendor.layouts.guest')

@section('title', 'Complete Registration')

@section('content')
<div class="vp-auth-card" style="max-width:630px;text-align:left;">
    <div style="text-align:center;">
        @include('vendor.partials.auth-logo')
        <p class="vp-auth-kicker">Vendor Partner</p>
        <h1 class="vp-auth-title">Complete Registration</h1>
        <p class="vp-auth-sub">Tell us about your business</p>
    </div>

    <form method="POST" action="{{ route('vendor.register.submit') }}" enctype="multipart/form-data" class="vp-auth-form">
        @csrf
        <input type="hidden" name="registration_token" value="{{ $registerSession['registration_token'] }}">
  <div class="vp-form-grid">
        <div class="vp-field">
            <label class="vp-label">Shop / Brand Name <span class="vp-required">*</span></label>
            <input type="text" name="shop_name" class="vp-input @error('shop_name') vp-input--error @enderror" value="{{ old('shop_name') }}" required maxlength="100" data-vp-restrict="title" placeholder="Enter Shop / Brand Name">
            @error('shop_name')<p class="vp-field-error">{{ $message }}</p>@enderror
        </div>
        <div class="vp-field">
            <label class="vp-label">Owner Name <span class="vp-required">*</span></label>
            <input type="text" name="owner_name" class="vp-input @error('owner_name') vp-input--error @enderror" value="{{ old('owner_name') }}" required maxlength="100" data-vp-restrict="person-name" placeholder="Enter Owner Name">
            @error('owner_name')<p class="vp-field-error">{{ $message }}</p>@enderror
        </div>
        <div class="vp-field">
            <label class="vp-label">Email-id <span class="vp-required">*</span></label>
            <input type="email" name="email" class="vp-input @error('email') vp-input--error @enderror" value="{{ old('email') }}" required maxlength="255" data-vp-restrict="email" placeholder="Enter Email-id">
            @error('email')<p class="vp-field-error">{{ $message }}</p>@enderror
        </div>
        <div class="vp-field">
            <label class="vp-label">City</label>
            <input type="text" name="city" class="vp-input @error('city') vp-input--error @enderror" value="{{ old('city') }}" maxlength="100" data-vp-restrict="city" placeholder="Enter city name">
            @error('city')<p class="vp-field-error">{{ $message }}</p>@enderror
        </div>
        </div>
        <div class="vp-field">
            <label class="vp-label">Service Types <span class="vp-required">*</span></label>
            <input type="text" name="service_types" class="vp-input @error('service_types') vp-input--error @enderror" value="{{ old('service_types', 'Fashion Designer, Rented Dress') }}" required maxlength="500" data-vp-restrict="text">
            @error('service_types')<p class="vp-field-error">{{ $message }}</p>@enderror
        </div>

        <div class="vp-field">
            <label class="vp-label">Aadhar Front <span class="vp-required">*</span></label>
            <input type="file" name="aadhar_front" class="vp-file vp-input" accept="image/jpeg,image/jpg,image/png,image/webp" required>
            @error('aadhar_front')<p class="vp-field-error">{{ $message }}</p>@enderror
        </div>
        <div class="vp-field">
            <label class="vp-label">Aadhar Back <span class="vp-required">*</span></label>
            <input type="file" name="aadhar_back" class="vp-file vp-input" accept="image/jpeg,image/jpg,image/png,image/webp" required>
            @error('aadhar_back')<p class="vp-field-error">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="vp-btn vp-btn--primary vp-btn--block" style="padding:.85rem;">Submit for Approval</button>
    </form>
</div>
@endsection
