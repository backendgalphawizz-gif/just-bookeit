<h2 class="vp-settings-panel-title">Security</h2>
<p style="margin:-.75rem 0 1.35rem;color:var(--vp-muted);font-size:.9rem;">Update your password to keep your account secure.</p>

<form method="POST" action="{{ route('vendor.settings.password') }}">
    @csrf

    <div class="vp-field">
        <label class="vp-label" for="password">New Password <span class="vp-required">*</span></label>
        <div class="vp-input-icon-wrap">
            @include('vendor.partials.nav-icon', ['icon' => 'lock'])
            <input id="password" type="password" name="password" class="vp-input @error('password') vp-input--error @enderror" placeholder="New password" required minlength="8" autocomplete="new-password">
            <button type="button" class="vp-input-toggle" data-toggle-password aria-label="Show password">
                @include('vendor.partials.nav-icon', ['icon' => 'eye'])
            </button>
        </div>
        <p class="vp-field-hint">Minimum 8 characters</p>
        @error('password')<p class="vp-field-error">{{ $message }}</p>@enderror
    </div>
    <div class="vp-field">
        <label class="vp-label" for="password_confirmation">Confirm Password <span class="vp-required">*</span></label>
        <div class="vp-input-icon-wrap">
            @include('vendor.partials.nav-icon', ['icon' => 'lock'])
            <input id="password_confirmation" type="password" name="password_confirmation" class="vp-input @error('password_confirmation') vp-input--error @enderror" placeholder="Confirm new password" required minlength="8" autocomplete="new-password">
            <button type="button" class="vp-input-toggle" data-toggle-password aria-label="Show password">
                @include('vendor.partials.nav-icon', ['icon' => 'eye'])
            </button>
        </div>
        @error('password_confirmation')<p class="vp-field-error">{{ $message }}</p>@enderror
    </div>

    <div class="vp-settings-panel-foot">
        <button type="submit" class="vp-btn vp-btn--primary">Update Password</button>
    </div>
</form>
