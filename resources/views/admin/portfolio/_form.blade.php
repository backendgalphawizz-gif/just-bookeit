@php
    use App\Support\VendorValidationRules;

    $isCreate = ! $portfolio->exists;
    $productImageMaxMb = (int) (VendorValidationRules::MAX_IMAGE_KB / 1024);
    $productVideoMaxMb = (int) (VendorValidationRules::MAX_VIDEO_KB / 1024);
    $galleryImages = $portfolio->relationLoaded('images')
        ? $portfolio->images->filter(fn ($media) => $media->isImage())->values()
        : collect();
    $galleryVideos = $portfolio->relationLoaded('images')
        ? $portfolio->images->filter(fn ($media) => $media->isVideo())->values()
        : collect();
    $selectedSubcategoryId = old('subcategory_id', $portfolio->subcategory_id);
    $selectedMainCategoryId = old('main_category_id', $portfolio->subcategory?->parent_id);
    $subcategoryOptions = $subcategories->map(fn ($sub) => [
        'id' => $sub->id,
        'name' => $sub->name,
        'parent_id' => $sub->parent_id,
    ])->values();
    $audienceByMainSlug = $mainCategories->mapWithKeys(fn ($main) => [$main->id => $main->slug])->all();
    $serviceCategorySlugs = $serviceCategories->mapWithKeys(fn ($category) => [(string) $category->id => $category->slug])->all();
    $selectedCategoryId = (string) old('category_id', $portfolio->category_id ?? '');
@endphp

<div
    class="jb-form-grid"
    x-data="{
        categoryId: @js($selectedCategoryId),
        categorySlugs: @js($serviceCategorySlugs),
        get productType() {
            return this.categorySlugs[String(this.categoryId)] || '';
        },
        get hideProductPricing() {
            return this.productType === 'rented-dress';
        },
        get showAdvanceAmount() {
            return this.productType === 'rented-jewellery';
        },
        get showVariants() {
            return this.productType === 'rented-dress';
        }
    }"
>
    <x-admin.form-select label="Vendor" name="vendor_id" :required="true">
        <option value="">Select vendor</option>
        @foreach ($vendors as $vendor)
            <option value="{{ $vendor->id }}" @selected(old('vendor_id', $portfolio->vendor_id) == $vendor->id)>{{ $vendor->brand_name }}</option>
        @endforeach
    </x-admin.form-select>

    <div>
        <label for="category_id" class="jb-label">Product type <span class="text-rose-600">*</span></label>
        <select id="category_id" name="category_id" class="jb-select" required x-model="categoryId">
            <option value="">Select type</option>
            @foreach ($serviceCategories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </select>
        @error('category_id')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div
        class="sm:col-span-2"
        x-data="{
            mainCategoryId: @js((string) ($selectedMainCategoryId ?? '')),
            subcategoryId: @js((string) ($selectedSubcategoryId ?? '')),
            subcategories: @js($subcategoryOptions),
            audienceByMain: @js($audienceByMainSlug),
            audience: @js(old('audience', $portfolio->audience ?? 'women')),
            init() {
                this.onSubChange();
                this.syncAudience();
            },
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
                {{-- Hidden input submits reliably; Alpine x-for options inside <select> often drop the value on submit. --}}
                <input type="hidden" name="subcategory_id" x-model="subcategoryId">
                <select id="subcategory_id" class="jb-select" x-model="subcategoryId" @change="onSubChange()" required>
                    <option value="">Select sub-category</option>
                    <template x-for="sub in filteredSubs()" :key="sub.id">
                        <option :value="String(sub.id)" x-text="sub.name" :selected="String(subcategoryId) === String(sub.id)"></option>
                    </template>
                </select>
                @error('subcategory_id')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <input type="hidden" name="audience" x-model="audience">
    </div>

    @include('admin.partials.form-input', [
        'label' => 'Title',
        'name' => 'title',
        'value' => old('title', $portfolio->title),
        'required' => true,
        'restrict' => 'title',
        'maxWords' => 24,
        {{-- 'hint' => 'Maximum 24 words.', --}}
    ])

    <div x-show="!hideProductPricing" x-cloak>
        <label for="price_per_day" class="jb-label">Price per day (₹) @if($isCreate)<span class="text-rose-600">*</span>@endif</label>
        <input
            id="price_per_day"
            type="number"
            name="price_per_day"
            value="{{ old('price_per_day', $portfolio->price_per_day) }}"
            class="jb-input"
            min="0"
            step="0.01"
            x-bind:required="!hideProductPricing && {{ $isCreate ? 'true' : 'false' }}"
            x-bind:disabled="hideProductPricing"
        >
        @error('price_per_day')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div x-show="showAdvanceAmount" x-cloak>
        <label for="advance_amount" class="jb-label">Advance amount (₹)</label>
        <input
            id="advance_amount"
            type="number"
            name="advance_amount"
            value="{{ old('advance_amount', $portfolio->advance_amount) }}"
            class="jb-input"
            min="0"
            step="0.01"
            x-bind:disabled="!showAdvanceAmount"
        >
        @error('advance_amount')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
    </div>

    <template x-if="hideProductPricing">
        <div>
            <input type="hidden" name="price_per_day" value="">
        </div>
    </template>
    <template x-if="!showAdvanceAmount">
        <div>
            <input type="hidden" name="advance_amount" value="">
        </div>
    </template>

    @include('admin.partials.form-input', [
        'label' => 'Description',
        'name' => 'description',
        'type' => 'textarea',
        'rows' => 4,
        'value' => old('description', $portfolio->description),
        'restrict' => 'text',
        'maxWords' => 150,
        {{-- 'hint' => 'Maximum 150 words.', --}}
        'full' => true,
    ])

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

    @include('admin.portfolio.partials.media-uploads')

    <div class="sm:col-span-2" x-show="showVariants" x-cloak x-bind:data-variants-enabled="showVariants ? '1' : '0'">
        @include('admin.portfolio.partials.variants')
    </div>
    @include('admin.portfolio.partials.damage-deductions')
</div>
