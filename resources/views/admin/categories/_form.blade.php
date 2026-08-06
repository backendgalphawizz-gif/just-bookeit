@php
    $category = $category ?? null;
    $type = $type ?? old('type', $category?->type ?? \App\Models\Category::TYPE_MAIN);
    $requiresParent = $type === \App\Models\Category::TYPE_SUB;
    $parentRequired = true;
    $isServiceEdit = $category && $type === \App\Models\Category::TYPE_SERVICE;
@endphp

<input type="hidden" name="type" value="{{ $type }}">

@if ($isServiceEdit)
    <div class="sm:col-span-2">
        <label class="jb-label">Name</label>
        <p class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-800">{{ $category->name }}</p>
        <p class="mt-1 text-xs text-slate-500">Service category names are fixed and cannot be changed.</p>
    </div>
@else
    @include('admin.partials.form-input', [
        'label' => 'Name',
        'name' => 'name',
        'restrict' => 'title',
        'value' => old('name', $category?->name),
        'required' => true,
    ])
@endif

@if ($requiresParent)
    <x-admin.form-select label="Parent category" name="parent_id" :required="$parentRequired">
        <option value="">{{ $parentRequired ? 'Select parent category' : 'None' }}</option>
        @foreach ($parents as $parent)
            <option value="{{ $parent->id }}" @selected(old('parent_id', $category?->parent_id) == $parent->id)>{{ $parent->name }}</option>
        @endforeach
    </x-admin.form-select>

    <x-admin.form-select label="Service type" name="service_category_id" :required="true">
        <option value="">Select service type</option>
        @foreach ($serviceCategories ?? [] as $serviceCategory)
            <option value="{{ $serviceCategory->id }}" @selected(old('service_category_id', $category?->service_category_id) == $serviceCategory->id)>{{ $serviceCategory->name }}</option>
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
    'hint' => 'Recommended resolution: 340 × 248 px · PNG, JPG, or WebP · max 4 MB',
])

@if (! $isServiceEdit)
    <div class="jb-checkbox-row sm:col-span-2">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category?->is_active ?? true))>
        <label class="text-sm font-medium text-slate-700">Active</label>
    </div>
@endif
