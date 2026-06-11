@php
    $isCreate = ! $portfolio->exists;
@endphp

<div class="jb-form-grid">
    <x-admin.form-select label="Vendor" name="vendor_id" :required="true">
        <option value="">Select vendor</option>
        @foreach ($vendors as $vendor)
            <option value="{{ $vendor->id }}" @selected(old('vendor_id', $portfolio->vendor_id) == $vendor->id)>{{ $vendor->brand_name }}</option>
        @endforeach
    </x-admin.form-select>

    <x-admin.form-select label="Product type" name="category_id" :required="true">
        <option value="">Select type</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected(old('category_id', $portfolio->category_id) == $category->id)>{{ $category->name }}</option>
        @endforeach
    </x-admin.form-select>

    @include('admin.partials.form-input', ['label' => 'Title', 'name' => 'title', 'value' => old('title', $portfolio->title), 'required' => true])

    <x-admin.form-select label="Audience" name="audience" :required="true">
        @foreach (['women' => 'Women', 'men' => 'Men', 'kids' => 'Kids'] as $value => $label)
            <option value="{{ $value }}" @selected(old('audience', $portfolio->audience ?? 'women') === $value)>{{ $label }}</option>
        @endforeach
    </x-admin.form-select>

    @include('admin.partials.form-input', ['label' => 'Price per day (₹)', 'name' => 'price_per_day', 'type' => 'number', 'value' => old('price_per_day', $portfolio->price_per_day), 'required' => $isCreate, 'step' => '0.01', 'nonNegative' => true])

    @include('admin.partials.form-input', ['label' => 'Advance amount (₹)', 'name' => 'advance_amount', 'type' => 'number', 'value' => old('advance_amount', $portfolio->advance_amount), 'step' => '0.01', 'nonNegative' => true])

    <div class="sm:col-span-2">
        <label for="description" class="jb-label">Description</label>
        <textarea id="description" name="description" rows="4" class="jb-input">{{ old('description', $portfolio->description) }}</textarea>
        @error('description')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2" x-data="{ status: @js(old('status', $portfolio->status ?? 'pending')) }">
        <label for="status" class="jb-label">Status</label>
        <select id="status" name="status" class="jb-select" required x-model="status">
            @foreach (['pending', 'approved', 'rejected'] as $status)
                <option value="{{ $status }}">{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        @error('status')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
        <div class="mt-4" x-show="status === 'rejected'" x-cloak>
            @include('admin.partials.form-input', ['label' => 'Rejection reason', 'name' => 'rejection_reason', 'type' => 'textarea', 'rows' => 2, 'value' => old('rejection_reason', $portfolio->rejection_reason), 'full' => true])
        </div>
    </div>

    <div class="sm:col-span-2">
        <label class="jb-label">Primary image @if($isCreate)<span class="text-rose-600">*</span>@endif</label>
        <p class="mb-3 text-sm text-slate-500">Main cover photo shown in listings.</p>
        @if ($portfolio->displayImageUrl())
            <img src="{{ $portfolio->displayImageUrl() }}" alt="{{ $portfolio->title }}" class="mb-3 h-32 w-32 rounded-xl object-cover ring-1 ring-slate-200 panel-lightbox-trigger">
        @endif
        <input type="file" name="image" accept="image/jpeg,image/jpg,image/png,image/webp" class="jb-input" {{ $isCreate ? 'required' : '' }}>
        <p class="mt-1.5 text-xs text-slate-500">JPEG, PNG or WebP — max 20 MB.</p>
        @error('image')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label class="jb-label">Gallery images</label>
        <p class="mb-3 text-sm text-slate-500">Additional photos customers can browse when booking (up to 10).</p>

        @if ($portfolio->relationLoaded('images') && $portfolio->images->isNotEmpty())
            <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                @foreach ($portfolio->images as $image)
                    <div class="relative overflow-hidden rounded-xl border border-slate-200">
                        @if ($image->imageUrl())
                            <img src="{{ $image->imageUrl() }}" alt="" class="aspect-square w-full object-cover panel-lightbox-trigger">
                        @endif
                        @if ($portfolio->exists)
                            <form method="POST" action="{{ route('admin.portfolio.images.destroy', [$portfolio, $image]) }}" class="absolute right-2 top-2">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg bg-white/95 px-2 py-1 text-xs font-semibold text-rose-600 shadow-sm hover:bg-white"
                                        data-jb-confirm="This gallery image will be permanently removed."
                                        data-jb-confirm-title="Remove image?"
                                        data-jb-confirm-label="Remove"
                                        data-jb-confirm-variant="error">
                                    Remove
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <input type="file" name="gallery_images[]" accept="image/jpeg,image/jpg,image/png,image/webp" multiple class="jb-input">
        @error('gallery_images')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
        @error('gallery_images.*')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
    </div>

    @include('admin.portfolio.partials.variants')
    @include('admin.portfolio.partials.damage-deductions')
</div>
