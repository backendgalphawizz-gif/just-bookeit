@php
    $size = $size ?? null;
@endphp

@include('admin.partials.form-input', [
    'label' => 'Size name',
    'name' => 'name',
    'restrict' => 'text',
    'value' => old('name', $size?->name),
    'required' => true,
    'placeholder' => 'e.g. XL',
])

@include('admin.partials.form-input', [
    'label' => 'Sort order',
    'name' => 'sort_order',
    'type' => 'number',
    'min' => '0',
    'max' => '9999',
    'value' => old('sort_order', $size?->sort_order ?? 0),
])

<div class="jb-checkbox-row sm:col-span-2">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $size?->is_active ?? true))>
    <label class="text-sm font-medium text-slate-700">Active</label>
</div>
