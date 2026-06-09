@php
    $banner = $banner ?? null;
    $audience = $audience ?? $banner?->audience ?? \App\Models\Banner::AUDIENCE_CUSTOMER;
@endphp
<input type="hidden" name="audience" value="{{ old('audience', $audience) }}">
<div class="sm:col-span-2">
    <p class="jb-label">Audience</p>
    <p class="text-sm font-semibold text-slate-800">
        @if ($audience === \App\Models\Banner::AUDIENCE_CUSTOMER)
            Customer website &amp; app
        @else
            {{ \App\Models\Banner::audienceLabel($audience) }} app
        @endif
    </p>
</div>
<div class="sm:col-span-2">
    @include('admin.partials.image-upload', [
        'label' => 'Banner image',
        'name' => 'image',
        'currentUrl' => $banner?->image_url,
        'required' => ! $banner || ! $banner->image_url,
    ])
</div>
@include('admin.partials.form-input', ['label' => 'Title', 'name' => 'title', 'value' => old('title', $banner?->title), 'required' => true])
@include('admin.partials.form-input', ['label' => 'Subtitle', 'name' => 'subtitle', 'value' => old('subtitle', $banner?->subtitle)])
@include('admin.partials.form-input', ['label' => 'Redirect URL', 'name' => 'redirect_url', 'type' => 'url', 'value' => old('redirect_url', $banner?->redirect_url), 'hint' => 'Optional link when the banner is tapped or clicked'])
@include('admin.partials.form-date-range', [
    'startValue' => $banner?->starts_at?->format('Y-m-d'),
    'endValue' => $banner?->ends_at?->format('Y-m-d'),
])
<div class="jb-checkbox-row sm:col-span-2">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $banner?->is_active ?? true))>
    <label class="text-sm font-medium text-slate-700">
        @if ($audience === \App\Models\Banner::AUDIENCE_VENDOR)
            Show on vendor app
        @elseif ($audience === \App\Models\Banner::AUDIENCE_DRIVER)
            Show on driver app
        @else
            Show on website &amp; customer app
        @endif
    </label>
</div>
