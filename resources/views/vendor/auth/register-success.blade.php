@extends('vendor.layouts.guest')

@section('title', 'Registration Submitted')

@section('content')
<div class="vp-register-success">
    <div class="vp-register-success-card">
        <div class="vp-register-success-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        <h1 class="vp-register-success-title">Success!</h1>
        <p class="vp-register-success-message">
            Your registration request has been approved! You can now access your vendor dashboard.
        </p>
    </div>
</div>

<script>
    setTimeout(function () {
        window.location.href = @json(route('vendor.dashboard'));
    }, 2000);
</script>
@endsection
