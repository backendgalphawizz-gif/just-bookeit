@php $banner = $banner ?? null; @endphp
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
@include('admin.partials.form-input', ['label' => 'CTA label', 'name' => 'cta_label', 'value' => old('cta_label', $banner?->cta_label)])
@include('admin.partials.form-input', ['label' => 'Redirect URL', 'name' => 'redirect_url', 'type' => 'url', 'value' => old('redirect_url', $banner?->redirect_url)])
@include('admin.partials.form-date-range', [
    'startValue' => $banner?->starts_at?->format('Y-m-d'),
    'endValue' => $banner?->ends_at?->format('Y-m-d'),
])
<div class="jb-checkbox-row sm:col-span-2">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $banner?->is_active ?? true))>
    <label class="text-sm font-medium text-slate-700">Show on homepage</label>
</div>
