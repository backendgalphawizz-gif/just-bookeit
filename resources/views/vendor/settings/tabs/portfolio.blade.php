<h2 class="vp-settings-panel-title">Portfolio</h2>

<form method="POST" action="{{ route('vendor.settings.portfolio') }}" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="tab" value="profile">
    <input type="hidden" name="upload_only" id="profile-upload-only" value="">

    <div class="vp-profile-layout">
        <div>
            <div class="vp-field">
                <label class="vp-label" for="owner_name">Profile Name <span class="vp-required">*</span></label>
                <div class="vp-input-icon-wrap">
                    @include('vendor.partials.nav-icon', ['icon' => 'user'])
                    <input id="owner_name" type="text" name="owner_name" class="vp-input @error('owner_name') vp-input--error @enderror" value="{{ old('owner_name', $vendor->owner_name) }}" placeholder="Enter your name" required maxlength="100" data-vp-restrict="person-name" autocomplete="name">
                </div>
                @error('owner_name')<p class="vp-field-error">{{ $message }}</p>@enderror
            </div>
            <div class="vp-field">
                <label class="vp-label" for="email">Email Address <span class="vp-required">*</span></label>
                <div class="vp-input-icon-wrap">
                    @include('vendor.partials.nav-icon', ['icon' => 'mail'])
                    <input id="email" type="email" name="email" class="vp-input @error('email') vp-input--error @enderror" value="{{ old('email', $vendor->email) }}" placeholder="you@example.com" required maxlength="255" data-vp-restrict="email" autocomplete="email">
                </div>
                @error('email')<p class="vp-field-error">{{ $message }}</p>@enderror
            </div>
            <div class="vp-field">
                <label class="vp-label" for="mobile">Phone Number</label>
                <div class="vp-input-icon-wrap">
                    @include('vendor.partials.nav-icon', ['icon' => 'phone'])
                    <input id="mobile" type="tel" name="mobile" class="vp-input @error('mobile') vp-input--error @enderror" value="{{ old('mobile', $vendor->mobile) }}" placeholder="10 digit mobile number" inputmode="numeric" maxlength="10" pattern="[0-9]{10}" data-vp-restrict="phone" autocomplete="tel">
                </div>
                <p class="vp-field-hint">10 digits only, no country code</p>
                @error('mobile')<p class="vp-field-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="vp-cover-block">
            <span class="vp-cover-label">Cover &amp; Profile Image</span>
            <!-- <p class="vp-field-hint" style="margin-bottom:.65rem;">The small circle photo appears in the header. The large image is your cover banner only.</p> -->
            <div class="vp-cover-frame">
                @if ($vendor->coverImageUrl())
                    <img src="{{ $vendor->coverImageUrl() }}" alt="Cover" class="panel-lightbox-trigger">
                @else
                    <div class="vp-cover-placeholder">Cover image</div>
                @endif
                <label class="vp-cover-edit" title="Change cover">
                    @include('vendor.partials.nav-icon', ['icon' => 'camera'])
                    <input type="file" name="cover_image" accept="image/jpeg,image/jpg,image/png,image/webp" style="display:none" data-vp-file-label="Cover image" data-vp-max-file-bytes="{{ \App\Support\VendorValidationRules::MAX_IMAGE_KB * 1024 }}" data-vp-upload-only="cover_image">
                </label>
                <div class="vp-profile-avatar-wrap">
                    @if ($vendor->avatarUrl())
                        <img src="{{ $vendor->avatarUrl() }}" alt="Profile" class="panel-lightbox-trigger">
                    @else
                        <div class="vp-profile-avatar-fallback">{{ $vendor->avatarInitial() }}</div>
                    @endif
                    <label class="vp-profile-edit" title="Change profile photo">
                        @include('vendor.partials.nav-icon', ['icon' => 'camera'])
                        <input type="file" name="profile_image" accept="image/jpeg,image/jpg,image/png,image/webp" style="display:none" data-vp-file-label="Profile image" data-vp-max-file-bytes="{{ \App\Support\VendorValidationRules::MAX_IMAGE_KB * 1024 }}" data-vp-upload-only="profile_image">
                    </label>
                </div>
            </div>
            @error('profile_image')<p class="vp-field-error">{{ $message }}</p>@enderror
            @error('cover_image')<p class="vp-field-error">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="vp-settings-panel-foot">
        <button type="submit" class="vp-btn vp-btn--primary" onclick="document.getElementById('profile-upload-only').value=''">
            @include('vendor.partials.nav-icon', ['icon' => 'save'])
            Save Changes
        </button>
    </div>
</form>
