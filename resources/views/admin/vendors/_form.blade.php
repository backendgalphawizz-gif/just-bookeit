@php $vendor = $vendor ?? null; @endphp
@include('admin.partials.form-input', ['label' => 'Brand Name', 'name' => 'brand_name', 'value' => old('brand_name', $vendor?->brand_name), 'required' => true])
@include('admin.partials.form-input', ['label' => 'Owner Name', 'name' => 'owner_name', 'value' => old('owner_name', $vendor?->owner_name), 'required' => true])
@include('admin.partials.form-input', ['label' => 'Mobile', 'name' => 'mobile', 'value' => old('mobile', $vendor?->mobile), 'required' => true])
@include('admin.partials.form-input', ['label' => 'Email', 'name' => 'email', 'type' => 'email', 'value' => old('email', $vendor?->email), 'required' => true])
@include('admin.partials.form-input', ['label' => 'City', 'name' => 'city', 'value' => old('city', $vendor?->city)])
<x-admin.form-select label="Status" name="status" :required="true">
    @foreach (['pending', 'active', 'suspended', 'rejected'] as $s)
        <option value="{{ $s }}" @selected(old('status', $vendor?->status ?? 'pending') === $s)>{{ ucfirst($s) }}</option>
    @endforeach
</x-admin.form-select>
@include('admin.partials.form-input', ['label' => 'Rating', 'name' => 'rating', 'type' => 'number', 'step' => '0.1', 'min' => '0', 'max' => '5', 'value' => old('rating', $vendor?->rating ?? 0)])
@include('admin.partials.form-input', ['label' => 'Orders Completed', 'name' => 'orders_completed', 'type' => 'number', 'step' => '1', 'value' => old('orders_completed', $vendor?->orders_completed ?? 0)])
@include('admin.partials.form-input', ['label' => 'Earnings (₹)', 'name' => 'earnings', 'type' => 'number', 'step' => '0.01', 'value' => old('earnings', $vendor?->earnings ?? 0)])
@include('admin.partials.form-input', ['label' => 'Categories (comma-separated)', 'name' => 'categories_text', 'value' => old('categories_text', implode(', ', $vendor?->categories ?? [])), 'full' => true])
