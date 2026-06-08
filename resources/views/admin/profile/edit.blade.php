@extends('admin.layouts.app')
@section('title', 'My Profile')
@section('page_title', 'My Profile')
@section('back_href', route('admin.dashboard'))
@section('back_label', '← Back to dashboard')
@section('page_subtitle', 'Update your account details and photo')

@section('content')
    <div class="jb-profile-page">
        <form
            method="POST"
            action="{{ route('admin.profile.update') }}"
            enctype="multipart/form-data"
            class="jb-card"
            x-data="{
                preview: @js($admin->avatar_url),
                pickFile(event) {
                    const file = event.target.files[0];
                    if (!file) {
                        return;
                    }
                    if (this._previewUrl) {
                        URL.revokeObjectURL(this._previewUrl);
                    }
                    this._previewUrl = URL.createObjectURL(file);
                    this.preview = this._previewUrl;
                },
            }"
        >
            @csrf
            @method('PUT')

            <div class="jb-card-header">
                <p class="jb-card-header-title">Account settings</p>
                <p class="text-sm text-slate-500">{{ $admin->role->name }}</p>
            </div>

            <div class="jb-card-body space-y-8">
                <section class="jb-profile-section">
                    <p class="jb-profile-section-title">Profile photo</p>
                    <div class="jb-profile-photo-panel">
                        <template x-if="preview">
                            <img :src="preview" alt="" class="jb-profile-avatar jb-profile-avatar--lg shrink-0 ring-4 ring-white shadow-md panel-lightbox-trigger">
                        </template>
                        <template x-if="!preview">
                            <span class="jb-profile-avatar jb-profile-avatar--lg jb-profile-avatar--initials shrink-0 ring-4 ring-white shadow-md">{{ $admin->initials() }}</span>
                        </template>
                        <div class="jb-profile-photo-meta">
                            <p class="text-sm font-semibold text-slate-800">Upload a new photo</p>
                            <p class="mt-1 text-sm text-slate-500">Square images work best. PNG or JPG, max 2MB.</p>
                            <label class="jb-profile-file-btn mt-4">
                                <span>Choose image</span>
                                <input
                                    type="file"
                                    name="avatar"
                                    accept="image/png,image/jpeg,image/jpg,image/webp"
                                    class="jb-profile-file-input"
                                    @change="pickFile($event)"
                                >
                            </label>
                            @error('avatar')
                                <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                <section class="jb-profile-section">
                    <p class="jb-profile-section-title">Account details</p>
                    <div class="jb-form-grid">
                        @include('admin.partials.form-input', ['label' => 'Full name', 'name' => 'name', 'value' => old('name', $admin->name), 'required' => true])
                        @include('admin.partials.form-input', ['label' => 'Email ID', 'name' => 'email', 'type' => 'email', 'value' => old('email', $admin->email), 'required' => true])
                        <div class="sm:col-span-2">
                            <label class="jb-label">Username</label>
                            <input type="text" class="jb-input cursor-not-allowed bg-slate-50 text-slate-600" value="{{ $admin->username }}" readonly disabled>
                            <p class="mt-1.5 text-xs text-slate-500">Username cannot be changed here.</p>
                        </div>
                    </div>
                </section>

                <section class="jb-profile-section">
                    <p class="jb-profile-section-title">Security</p>
                    <div class="jb-form-grid">
                        @include('admin.partials.form-input', [
                            'label' => 'New password (optional)',
                            'name' => 'password',
                            'type' => 'password',
                            'full' => true,
                            'placeholder' => 'Leave blank to keep current password',
                        ])
                    </div>
                </section>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 px-4 py-4 sm:flex-row sm:px-6">
                <x-admin.button variant="secondary" :href="route('admin.dashboard')" class="w-full sm:w-auto justify-center">Cancel</x-admin.button>
                <x-admin.button variant="primary" type="submit" class="w-full sm:w-auto justify-center">Save profile</x-admin.button>
            </div>
        </form>
    </div>
@endsection
