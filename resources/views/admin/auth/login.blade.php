@extends('admin.layouts.guest')

@section('title', 'Admin Login')

@section('content')
    <div class="jb-login-card" x-data="{ showPassword: false }">
        <aside class="jb-login-brand" aria-hidden="false">
            <img
                src="{{ $loginBranding['logo_url'] }}"
                alt="{{ $loginBranding['name'] }}"
                class="jb-login-brand-logo"
            >
            <h2 class="jb-login-brand-title">{{ $loginBranding['name'] }}</h2>
            <p class="jb-login-brand-tagline">Fashion &amp; Lifestyle Admin Management System</p>
        </aside>

        <section class="jb-login-form-panel">
            <img
                src="{{ $loginBranding['logo_url'] }}"
                alt=""
                class="jb-login-form-logo"
                aria-hidden="true"
            >

            <header class="jb-login-form-header">
                <h1 class="jb-login-title">Sign in to your account</h1>
                <p class="jb-login-subtitle">Enter your credentials to access the admin dashboard.</p>
            </header>

            @if (session('error'))
                <div class="jb-login-banner jb-login-banner--error" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            @if (session('success'))
                <div class="jb-login-banner jb-login-banner--success" role="status">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.submit') }}" class="jb-login-form">
                @csrf

                <div>
                    <label for="login" class="jb-login-label">Email ID or username</label>
                    <input
                        id="login"
                        name="login"
                        type="text"
                        value="{{ old('login') }}"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="admin@justbookit.com"
                        maxlength="255"
                        data-jb-restrict="login-or-username"
                        class="jb-login-input"
                    >
                    @error('login')
                        <p class="jb-login-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="jb-login-label">Password</label>
                    <div class="jb-login-password-wrap">
                        <input
                            id="password"
                            name="password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            autocomplete="current-password"
                            placeholder="Enter your password"
                            class="jb-login-input jb-login-input--password"
                        >
                        <button
                            type="button"
                            class="jb-login-password-toggle"
                            @click="showPassword = !showPassword"
                            :aria-label="showPassword ? 'Hide password' : 'Show password'"
                            tabindex="-1"
                        >
                            <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="jb-login-error">{{ $message }}</p>
                    @enderror
                </div>

                <label class="jb-login-remember">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                    <span>Remember me</span>
                </label>

                <button type="submit" class="jb-login-submit">Sign In</button>
            </form>
        </section>
    </div>
@endsection
