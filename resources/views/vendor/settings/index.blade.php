@extends('vendor.layouts.app')

@section('title', 'Settings')

@section('content')
<div class="vp-settings-head">
    <div>
        <h1 class="vp-page-title">Vendor Profile</h1>
        <p class="vp-page-sub">Manage your account settings and preferences</p>
    </div>
    <form method="POST" action="{{ route('vendor.settings.toggle-active') }}" class="vp-toggle-wrap">
        @csrf
        <span class="vp-toggle-label">Active Status</span>
        <label class="vp-toggle">
            <input type="hidden" name="is_listing_active" value="0">
            <input type="checkbox" name="is_listing_active" value="1" @checked($vendor->is_listing_active) onchange="this.form.submit()">
            <span class="vp-toggle-track"></span>
        </label>
    </form>
</div>

<div class="vp-settings-layout">
    <div class="vp-card vp-settings-nav-card">
        <nav class="vp-settings-nav">
            @foreach ($settingsTabs as $navTab)
                <a href="{{ route('vendor.settings.index', ['tab' => $navTab['key']]) }}"
                   class="{{ $tab === $navTab['key'] ? 'active' : '' }}">
                    @include('vendor.partials.nav-icon', ['icon' => $navTab['icon']])
                    {{ $navTab['label'] }}
                </a>
            @endforeach
        </nav>
    </div>

    <div class="vp-card vp-settings-panel">
        @include('vendor.settings.tabs.'.$tab)
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const input = btn.closest('.vp-input-icon-wrap')?.querySelector('input');
        if (!input) return;
        input.type = input.type === 'password' ? 'text' : 'password';
    });
});
document.querySelectorAll('[data-editor-cmd]').forEach((btn) => {
    btn.addEventListener('click', (e) => {
        e.preventDefault();
        const cmd = btn.dataset.editorCmd;
        const area = document.getElementById('bio-editor');
        if (!area) return;
        area.focus();
        if (cmd === 'bold') document.execCommand('bold');
        if (cmd === 'italic') document.execCommand('italic');
        if (cmd === 'underline') document.execCommand('underline');
    });
});
</script>
@endpush
