@php
    $category = $category ?? null;
    $type = $type ?? old('type', $category?->type ?? \App\Models\Category::TYPE_MAIN);
@endphp

<input type="hidden" name="type" value="{{ $type }}">

@include('admin.partials.form-input', [
    'label' => 'Name',
    'name' => 'name',
    'restrict' => 'title',
    'value' => old('name', $category?->name),
    'required' => true,
])

@if ($type === \App\Models\Category::TYPE_SERVICE)
    <x-admin.form-select label="Category" name="parent_id">
        <option value="">None</option>
        @foreach ($parents as $parent)
            <option value="{{ $parent->id }}" @selected(old('parent_id', $category?->parent_id) == $parent->id)>{{ $parent->name }}</option>
        @endforeach
    </x-admin.form-select>
@endif

@include('admin.partials.form-input', [
    'label' => 'Sort order',
    'name' => 'sort_order',
    'type' => 'number',
    'value' => old('sort_order', $category?->sort_order ?? 0),
    'hint' => 'Lower numbers appear first in dropdowns.',
])

@include('admin.partials.logo-upload', [
    'name' => 'image',
    'label' => 'Category image',
    'currentUrl' => $category?->imageUrl(),
])

<div class="jb-checkbox-row sm:col-span-2">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category?->is_active ?? true))>
    <label class="text-sm font-medium text-slate-700">Active</label>
</div>
