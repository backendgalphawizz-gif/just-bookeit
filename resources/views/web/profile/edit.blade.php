@extends('web.layouts.profile')

@section('title', 'Edit Profile')

@section('content')
    <div class="jbw-card jbw-profile-edit-card jbw-profile-panel">
        <div class="jbw-profile-panel-head">
            <h2 class="jbw-profile-panel-title">Edit Profile</h2>
            <p class="jbw-profile-panel-sub">Update your personal details and how we can reach you.</p>
        </div>

        <form method="POST" action="{{ route('web.profile.update') }}" enctype="multipart/form-data" class="jbw-profile-edit-form">
            @csrf
            @method('PUT')

            <div class="jbw-profile-edit-photo-row" data-profile-photo-preview>
                @if ($customer->profileImageUrl())
                    <img src="{{ $customer->profileImageUrl() }}" alt="" class="jbw-profile-edit-photo" data-profile-photo-img>
                @else
                    <span class="jbw-profile-edit-photo jbw-profile-edit-photo--fallback" data-profile-photo-fallback>{{ strtoupper(substr($customer->name, 0, 1)) }}</span>
                    <img src="" alt="" class="jbw-profile-edit-photo" data-profile-photo-img hidden>
                @endif
                <div class="jbw-profile-edit-photo-body">
                    <label class="jbw-label" for="profile_image">Profile photo</label>
                    <input id="profile_image" type="file" name="profile_image" accept="{{ \App\Support\MediaUploadSupport::acceptAttribute('image') }}" class="jbw-input jbw-profile-edit-photo-input" data-profile-photo-input>
                    <p class="jbw-profile-edit-hint">JPG or PNG, up to 4 MB.</p>
                    @error('profile_image')<p class="jbw-field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="jbw-profile-edit-fields">
                <div class="jbw-field">
                    <label class="jbw-label" for="name">Full name</label>
                    <input id="name" type="text" name="name" class="jbw-input" value="{{ old('name', $customer->name) }}" required>
                    @error('name')<p class="jbw-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="jbw-field">
                    <label class="jbw-label" for="email">Email address</label>
                    <input id="email" type="email" name="email" class="jbw-input" value="{{ old('email', $customer->email) }}">
                    @error('email')<p class="jbw-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="jbw-field">
                    <label class="jbw-label" for="mobile">Mobile</label>
                    <input id="mobile" type="tel" class="jbw-input" value="{{ $customer->mobile }}" disabled>
                    <p class="jbw-profile-edit-hint">Mobile number cannot be changed here.</p>
                </div>
                <div class="jbw-field">
                    <label class="jbw-label" for="city">City</label>
                    <input id="city" type="text" name="city" class="jbw-input" value="{{ old('city', $customer->city) }}">
                    @error('city')<p class="jbw-field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="jbw-profile-edit-actions">
                <button type="submit" class="jbw-btn jbw-btn--primary">Save changes</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var root = document.querySelector('[data-profile-photo-preview]');
    if (!root) return;

    var input = root.querySelector('[data-profile-photo-input]');
    var img = root.querySelector('[data-profile-photo-img]');
    var fallback = root.querySelector('[data-profile-photo-fallback]');
    if (!input || !img) return;

    var objectUrl = null;

    input.addEventListener('change', function () {
        var file = input.files && input.files[0];
        if (!file || !file.type.match(/^image\//)) return;

        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
        }
        objectUrl = URL.createObjectURL(file);
        img.src = objectUrl;
        img.hidden = false;
        if (fallback) fallback.hidden = true;

        var sidebarPhoto = document.querySelector('.jbw-profile-card-photo-wrap');
        if (sidebarPhoto) {
            var sidebarImg = sidebarPhoto.querySelector('img.jbw-profile-card-photo');
            var sidebarFallback = sidebarPhoto.querySelector('.jbw-profile-card-photo--fallback');
            if (!sidebarImg) {
                sidebarImg = document.createElement('img');
                sidebarImg.className = 'jbw-profile-card-photo';
                sidebarImg.alt = '';
                sidebarPhoto.insertBefore(sidebarImg, sidebarPhoto.firstChild);
            }
            sidebarImg.src = objectUrl;
            sidebarImg.hidden = false;
            if (sidebarFallback) sidebarFallback.hidden = true;
        }
    });
})();
</script>
@endpush
