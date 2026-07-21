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
            return ['rented-dress', 'rented-jewellery'].includes(this.productType);
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

    @include('admin.partials.form-input', ['label' => 'Title', 'name' => 'title', 'value' => old('title', $portfolio->title), 'required' => true])

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

    <div x-show="!hideProductPricing" x-cloak>
        <label for="advance_amount" class="jb-label">Advance amount (₹)</label>
        <input
            id="advance_amount"
            type="number"
            name="advance_amount"
            value="{{ old('advance_amount', $portfolio->advance_amount) }}"
            class="jb-input"
            min="0"
            step="0.01"
            x-bind:disabled="hideProductPricing"
        >
        @error('advance_amount')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
    </div>

    <template x-if="hideProductPricing">
        <div>
            <input type="hidden" name="price_per_day" value="">
            <input type="hidden" name="advance_amount" value="">
        </div>
    </template>

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
        <input
            type="file"
            name="image"
            accept="image/jpeg,image/jpg,image/png,image/webp"
            class="jb-input vp-input"
            data-jb-max-mb="{{ $productImageMaxMb }}"
            data-jb-file-label="Primary image"
            {{ $isCreate ? 'required' : '' }}
        >
        <p class="mt-1.5 text-xs text-slate-500">JPEG, PNG or WebP — max {{ $productImageMaxMb }} MB.</p>
        @error('image')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label class="jb-label">Gallery images</label>
        <p class="mb-3 text-sm text-slate-500">Additional photos customers can browse when booking (up to 10).</p>

        @if ($galleryImages->isNotEmpty())
            <div class="mb-4 grid grid-cols-4 gap-2 sm:grid-cols-5 md:grid-cols-6">
                @foreach ($galleryImages as $image)
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

        <input
            type="file"
            name="gallery_images[]"
            accept="image/jpeg,image/jpg,image/png,image/webp"
            multiple
            class="jb-input vp-input"
            data-jb-max-mb="{{ $productImageMaxMb }}"
            data-jb-file-label="Gallery image"
        >
        <p class="mt-1.5 text-xs text-slate-500">Up to 10 images — max {{ $productImageMaxMb }} MB each.</p>
        @error('gallery_images')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
        @error('gallery_images.*')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label class="jb-label">Gallery videos</label>
        <p class="mb-3 text-sm text-slate-500">Product videos customers can watch (same as vendor app — up to 5).</p>

        @if ($galleryVideos->isNotEmpty())
            <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                @foreach ($galleryVideos as $video)
                    <div class="relative overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                        @if ($video->mediaUrl())
                            <video
                                src="{{ $video->mediaUrl() }}"
                                class="aspect-video w-full object-cover"
                                controls
                                playsinline
                                preload="metadata"
                            ></video>
                        @endif
                        @if ($portfolio->exists)
                            <button
                                type="button"
                                class="absolute right-1 top-1 rounded bg-white/95 px-1.5 py-0.5 text-[10px] font-semibold text-rose-600 shadow-sm hover:bg-white"
                                onclick="if (confirm('This gallery video will be permanently removed.')) document.getElementById('delete-image-{{ $video->id }}').submit()"
                            >
                                Remove
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <input
            type="file"
            name="gallery_videos[]"
            accept="video/mp4,video/webm,video/quicktime,video/x-m4v,video/x-msvideo,video/x-matroska,.mp4,.mov,.webm,.mkv,.avi,.m4v"
            multiple
            class="jb-input vp-input"
            data-jb-max-mb="{{ $productVideoMaxMb }}"
            data-jb-file-label="Gallery video"
        >
        <p class="mt-1.5 text-xs text-slate-500">Up to 5 videos — MP4/MOV/WEBM etc., max {{ $productVideoMaxMb }} MB each.</p>
        @error('gallery_videos')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
        @error('gallery_videos.*')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2" x-show="showVariants" x-cloak x-bind:data-variants-enabled="showVariants ? '1' : '0'">
        @include('admin.portfolio.partials.variants')
    </div>
    @include('admin.portfolio.partials.damage-deductions')
</div>
