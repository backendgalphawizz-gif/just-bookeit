@extends('web.layouts.profile')

@section('title', 'Edit Profile')
@section('page_title', 'Profile')
@section('page_subtitle', 'Update your personal details and how we can reach you.')

@section('content')
    <div class="jbw-card">
        <div class="jbw-page-head paddingtop">
                                    <h1 class="jbw-page-title fontsize">Personal Information</h1>

                            </div>
        <form method="POST" action="{{ route('web.profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <!-- @if ($customer->profileImageUrl())
                <img src="{{ $customer->profileImageUrl() }}" alt="" class="jbw-profile-edit-photo">
            @else
                <span class="jbw-profile-edit-photo jbw-profile-edit-photo--fallback">{{ strtoupper(substr($customer->name, 0, 1)) }}</span>
            @endif -->

            <label class="jbw-label" for="profile_image">Profile photo</label>
            <input id="profile_image" type="file" name="profile_image" accept="image/*" class="jbw-input" style="padding:0.5rem">

            <div style="height:1rem"></div>
            <div class="jbw-measure-form-grid">
                <div>
                    <label class="jbw-label" for="name">Full name</label>
                    <input id="name" type="text" name="name" class="jbw-input" value="{{ old('name', $customer->name) }}" required>
                </div>
                <div>
                    <label class="jbw-label" for="email">Email address</label>
                    <input id="email" type="email" name="email" class="jbw-input" value="{{ old('email', $customer->email) }}">
                </div>
                <div>
                    <label class="jbw-label" for="city">City</label>
                    <input id="city" type="text" name="city" class="jbw-input" value="{{ old('city', $customer->city) }}">
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;margin-top:1.25rem">
                <button type="submit" class="jbw-btn jbw-btn--primary">Save changes</button>
            </div>
        </form>
    </div>
@endsection
