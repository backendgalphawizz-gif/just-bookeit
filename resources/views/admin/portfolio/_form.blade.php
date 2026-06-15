@php
    $isCreate = ! $portfolio->exists;
    $selectedSubcategoryId = old('subcategory_id', $portfolio->subcategory_id);
    $selectedMainCategoryId = old('main_category_id', $portfolio->subcategory?->parent_id);
    $subcategoryOptions = $subcategories->map(fn ($sub) => [
        'id' => $sub->id,
        'name' => $sub->name,
        'parent_id' => $sub->parent_id,
    ])->values();
    $audienceByMainSlug = $mainCategories->mapWithKeys(fn ($main) => [$main->id => $main->slug])->all();
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
        @foreach ($serviceCategories as $category)
            <option value="{{ $category->id }}" @selected(old('category_id', $portfolio->category_id) == $category->id)>{{ $category->name }}</option>
        @endforeach
    </x-admin.form-select>

    <div
        class="sm:col-span-2"
        x-data="{
            mainCategoryId: @js((string) ($selectedMainCategoryId ?? '')),
            subcategoryId: @js((string) ($selectedSubcategoryId ?? '')),
            subcategories: @js($subcategoryOptions),
            audienceByMain: @js($audienceByMainSlug),
            audience: @js(old('audience', $portfolio->audience ?? 'women')),
            filteredSubs() {
                if (!this.mainCategoryId) return [];
                return this.subcategories.filter((sub) => String(sub.parent_id) === String(this.mainCategoryId));
            },
            syncAudience() {
                const slug = this.audienceByMain[this.mainCategoryId];
                if (slug) this.audience = slug;
            },
            onMainChange() {
                this.subcategoryId = '';
                this.syncAudience();
            },
            onSubChange() {
                const sub = this.subcategories.find((item) => String(item.id) === String(this.subcategoryId));
                if (sub) {
                    this.mainCategoryId = String(sub.parent_id);
                    this.syncAudience();
                }
            }
        }"
    >
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="jb-label" for="main_category_id">Category <span class="text-rose-600">*</span></label>
                <select
                    id="main_category_id"
                    name="main_category_id"
                    class="jb-select"
                    x-model="mainCategoryId"
                    @change="onMainChange()"
                    required
                >
                    <option value="">Select category</option>
                    @foreach ($mainCategories as $mainCategory)
                        <option value="{{ $mainCategory->id }}">{{ $mainCategory->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="jb-label" for="subcategory_id">Sub-category <span class="text-rose-600">*</span></label>
                <select id="subcategory_id" name="subcategory_id" class="jb-select" x-model="subcategoryId" @change="onSubChange()" required>
                    <option value="">Select sub-category</option>
                    <template x-for="sub in filteredSubs()" :key="sub.id">
                        <option :value="sub.id" x-text="sub.name"></option>
                    </template>
                </select>
                @error('subcategory_id')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <input type="hidden" name="audience" x-model="audience">
    </div>

    @include('admin.partials.form-input', ['label' => 'Title', 'name' => 'title', 'value' => old('title', $portfolio->title), 'required' => true])

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
            <img src="{{ $portfolio->displayImageUrl() }}" alt="{{ $portfolio->title }}" class="mb-3 h-20 w-20 rounded-xl object-cover ring-1 ring-slate-200 panel-lightbox-trigger">
        @endif
        <input type="file" name="image" accept="image/jpeg,image/jpg,image/png,image/webp" class="jb-input" {{ $isCreate ? 'required' : '' }}>
        <p class="mt-1.5 text-xs text-slate-500">JPEG, PNG or WebP — max 20 MB.</p>
        @error('image')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label class="jb-label">Gallery images</label>
        <p class="mb-3 text-sm text-slate-500">Additional photos customers can browse when booking (up to 10).</p>

        @if ($portfolio->relationLoaded('images') && $portfolio->images->isNotEmpty())
            <div class="mb-4 grid grid-cols-4 gap-2 sm:grid-cols-5 md:grid-cols-6">
                @foreach ($portfolio->images as $image)
                    <div class="relative h-20 w-20 overflow-hidden rounded-lg border border-slate-200">
                        @if ($image->imageUrl())
                            <img src="{{ $image->imageUrl() }}" alt="" class="h-full w-full object-cover panel-lightbox-trigger">
                        @endif
                        @if ($portfolio->exists)
                            <button
                                type="button"
                                class="absolute right-1 top-1 rounded bg-white/95 px-1.5 py-0.5 text-[10px] font-semibold text-rose-600 shadow-sm hover:bg-white"
                                onclick="if (confirm('This gallery image will be permanently removed.')) document.getElementById('delete-image-{{ $image->id }}').submit()"
                            >
                                Remove
                            </button>
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
