@php $category = $category ?? null; @endphp
@include('admin.partials.form-input', ['label' => 'Name', 'name' => 'name', 'restrict' => 'title', 'value' => old('name', $category?->name), 'required' => true])
<x-admin.form-select label="Type" name="type" :required="true">
    <option value="main" @selected(old('type', $category?->type) === 'main')>Main (Women / Men / Kids)</option>
    <option value="service" @selected(old('type', $category?->type) === 'service')>Service</option>
</x-admin.form-select>
<x-admin.form-select label="Parent category" name="parent_id">
    <option value="">None</option>
    @foreach ($parents as $parent)
        <option value="{{ $parent->id }}" @selected(old('parent_id', $category?->parent_id) == $parent->id)>{{ $parent->name }}</option>
    @endforeach
</x-admin.form-select>
@include('admin.partials.logo-upload', [
    'name' => 'image',
    'label' => 'Category / service image',
    'currentUrl' => $category?->imageUrl(),
])
<div class="jb-checkbox-row sm:col-span-2">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category?->is_active ?? true))>
    <label class="text-sm font-medium text-slate-700">Active</label>
</div>
