@extends('web.layouts.guest')

@section('title', 'Create account')

@section('content')
    <x-web.auth-shell title="Sign up to your Account" subtitle="Create account to continue!">
        <form method="POST" action="{{ route('web.register.submit') }}" class="jbw-form-stack">
            @csrf
            <input type="hidden" name="registration_token" value="{{ $registerSession['registration_token'] }}">

            <div class="jbw-field">
                <label class="jbw-label" for="name">Full name</label>
                <input id="name" type="text" name="name" class="jbw-input jbw-input--auth" value="{{ old('name') }}" placeholder="Sarah Shah" required autofocus>
            </div>

            <div class="jbw-field">
                <label class="jbw-label" for="mobile_display">Mobile number</label>
                <input id="mobile_display" type="text" class="jbw-input jbw-input--auth" value="+91 {{ $registerSession['mobile'] }}" readonly tabindex="-1">
            </div>

            <div class="jbw-field">
                <label class="jbw-label" for="email">Email address</label>
                <input id="email" type="email" name="email" class="jbw-input jbw-input--auth" value="{{ old('email') }}" placeholder="sarah@example.com">
            </div>

            <button type="submit" class="jbw-btn jbw-btn--primary jbw-btn--block jbw-btn--cta">Continue</button>
        </form>
    </x-web.auth-shell>
@endsection
