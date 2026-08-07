@php
    $browseServices = $webServiceCategories ?? collect();
    $browseCategories = $webBrowseCategories ?? collect();
    $browseServiceFallbacks = [
        'https://images.unsplash.com/photo-1469334031218-e382a71b716b?w=900&q=85&fit=crop',
        'https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=900&q=85&fit=crop',
        'https://images.unsplash.com/photo-1617032210317-3b0855f047a4?w=900&q=85&fit=crop',
    ];
    $browseGenderFallbacks = [
        'women' => 'https://images.unsplash.com/photo-1529626455594-4ff0802cfb7e?auto=format&fit=crop&w=500&q=80',
        'men' => 'https://images.unsplash.com/photo-1507679799987-c73779587ccf?auto=format&fit=crop&w=500&q=80',
        'kids' => 'https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?auto=format&fit=crop&w=500&q=80',
    ];
    $browseSubFallbacks = [
        'https://images.unsplash.com/photo-1594938298603-c8148c4dae35?w=700&q=80&fit=crop',
        'https://images.unsplash.com/photo-1610030469983-98e550d6193c?w=700&q=80&fit=crop',
        'https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=700&q=80&fit=crop',
        'https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=700&q=80&fit=crop',
    ];
    $browseGenderCategories = $browseCategories->keyBy(fn ($category) => strtolower((string) ($category->slug ?? $category->name)));
    $browseSubcategoryTree = $browseCategories->mapWithKeys(function ($category) use ($browseSubFallbacks) {
        $key = strtolower((string) ($category->slug ?: $category->name));

        return [
            $key => [
                'id' => $category->id,
                'name' => $category->name,
                'subs' => $category->subcategories->values()->map(function ($sub, $index) use ($browseSubFallbacks) {
                    return [
                        'id' => $sub->id,
                        'name' => $sub->name,
                        'service_category_id' => $sub->service_category_id,
                        'image' => $sub->imageUrl() ?: $browseSubFallbacks[$index % count($browseSubFallbacks)],
                    ];
                })->all(),
            ],
        ];
    })->all();
    $browseCatalogUrl = route('web.catalog.index');
    $browseHomeUrl = route('web.home');
@endphp

<!-- Category audience modal -->
<div id="jbwGenderModal" class="jbw-modal-overlay" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="jbwCategoryTitle">
    <div class="jbw-modal-content jbw-modal-content--category">
        <button type="button" class="jbw-modal-close" onclick="closeGenderModal()" aria-label="Close">&times;</button>
        <div class="jbw-category-head jbw-category-head--with-back">
            <a href="{{ $browseHomeUrl }}" class="jbw-catalog-back jbw-modal-back" aria-label="Go back" onclick="return browseFlowGoHome(event)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 18l-6-6 6-6"/></svg>
            </a>
            <div>
                <h2 id="jbwCategoryTitle" class="jbw-category-title">Category</h2>
                <p class="jbw-category-sub">Choose the Category you are looking for.</p>
            </div>
        </div>
        <div class="jbw-modal-options-grid">
            @foreach (['women' => 'WOMEN', 'men' => 'Men', 'kids' => 'KIDS'] as $genderKey => $genderLabel)
                @php
                    $genderCategory = $browseGenderCategories->get($genderKey);
                    $genderImage = $genderCategory?->imageUrl() ?: ($browseGenderFallbacks[$genderKey] ?? null);
                @endphp
                @if ($genderCategory)
                <button type="button" class="jbw-modal-option" onclick="selectGender('{{ $genderKey }}')">
                    <div class="jbw-modal-circle-thumb">
                        @if ($genderImage)
                            <img src="{{ $genderImage }}" alt="{{ $genderLabel }}">
                        @endif
                    </div>
                    <span class="jbw-modal-option-label">{{ $genderLabel }}</span>
                </button>
                @endif
            @endforeach
        </div>
    </div>
</div>

<!-- Services modal -->
<div id="jbwServicesModal" class="jbw-modal-overlay" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="jbwServicesTitle">
    <div class="jbw-modal-content jbw-modal-content--category">
        <button type="button" class="jbw-modal-close" onclick="closeServicesModal()" aria-label="Close">&times;</button>
        <div class="jbw-category-head">
            <h2 id="jbwServicesTitle" class="jbw-category-title">Services</h2>
            <p class="jbw-category-sub">Choose the Service you are looking for.</p>
        </div>
        <div class="jbw-modal-options-grid">
            @forelse ($browseServices as $index => $service)
                <button type="button" class="jbw-modal-option jbw-service-option" data-service-id="{{ (int) $service->id }}" onclick="selectService({{ (int) $service->id }})">
                    <div class="jbw-modal-circle-thumb">
                        <img src="{{ $service->imageUrl() ?: $browseServiceFallbacks[$index % count($browseServiceFallbacks)] }}" alt="{{ $service->name }}">
                    </div>
                    <span class="jbw-modal-option-label">{{ $service->name }}</span>
                </button>
            @empty
                @foreach (['Fashion Designer', 'Rented Dress', 'Rented Jewelry'] as $i => $label)
                    <button type="button" class="jbw-modal-option" onclick="selectService(null)">
                        <div class="jbw-modal-circle-thumb">
                            <img src="{{ $browseServiceFallbacks[$i] }}" alt="{{ $label }}">
                        </div>
                        <span class="jbw-modal-option-label">{{ $label }}</span>
                    </button>
                @endforeach
            @endforelse
        </div>
        <div id="jbwServicesEmpty" class="jbw-modal-empty" hidden>
            <div class="jbw-modal-empty-icon" aria-hidden="true">
                <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <p class="jbw-modal-empty-title">No products or subcategories found</p>
            <p class="jbw-modal-empty-text">Try another category or check back later.</p>
        </div>
    </div>
</div>

<!-- Sub-category modal -->
<div id="jbwSubcategoryModal" class="jbw-modal-overlay" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="jbwSubcategoryTitle">
    <div class="jbw-modal-content jbw-modal-content--subcats">
        <button type="button" class="jbw-modal-close" onclick="closeSubcategoryModal()" aria-label="Close">&times;</button>
        <div class="jbw-subcat-head">
            <h2 id="jbwSubcategoryTitle" class="jbw-subcat-title">Women</h2>
            <p class="jbw-subcat-sub">Choose the type of outfit you are looking for from our curated categories.</p>
        </div>
        <div id="jbwSubcategoryGrid" class="jbw-subcat-grid"></div>
        <div id="jbwSubcategoryEmpty" class="jbw-modal-empty" hidden>
            <div class="jbw-modal-empty-icon" aria-hidden="true">
                <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <p class="jbw-modal-empty-title">No products or subcategories found</p>
            <p class="jbw-modal-empty-text">Try another category or service, or check back later.</p>
        </div>
    </div>
</div>

<script>
    const jbwSubcategoryTree = @json($browseSubcategoryTree);
    const jbwCatalogBaseUrl = @json($browseCatalogUrl);
    const jbwHomeUrl = @json($browseHomeUrl);
    const jbwServicesUrl = @json(route('web.services.index'));
    const jbwVendorShowBase = @json(url('/designers'));
    let currentServiceId = null;
    let currentMainCategory = null;
    let pendingGenderKey = null;
    let browseNeedsService = false;
    let pendingVendorId = null;
    let vendorBrowseMode = 'products'; // 'products' | 'portfolio'
    let browseBackUrl = jbwHomeUrl;

    function catalogParams(extra = {}) {
        const params = new URLSearchParams();
        Object.entries(extra).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== '') {
                params.set(key, String(value));
            }
        });
        if (pendingVendorId && vendorBrowseMode !== 'portfolio') {
            params.set('vendor', String(pendingVendorId));
        }
        return params;
    }

    function goToVendorPortfolio(extra = {}) {
        if (!pendingVendorId) {
            return false;
        }
        const params = new URLSearchParams();
        Object.entries(extra).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== '') {
                params.set(key, String(value));
            }
        });
        const query = params.toString();
        window.location.href = jbwVendorShowBase + '/' + pendingVendorId + '/portfolio' + (query ? '?' + query : '');
        return true;
    }

    function anyHomeModalOpen() {
        return ['jbwGenderModal', 'jbwServicesModal', 'jbwSubcategoryModal']
            .some((id) => document.getElementById(id)?.style.display === 'flex');
    }

    function unlockBodyIfNoModals() {
        if (!anyHomeModalOpen()) {
            document.body.style.overflow = '';
        }
    }

    function browseFlowGoHome(event) {
        if (event) {
            event.preventDefault();
        }
        closeGenderModal();
        closeServicesModal();
        closeSubcategoryModal();
        window.location.href = browseBackUrl || jbwHomeUrl;
        return false;
    }

    function openCategoryBrowse() {
        currentServiceId = null;
        pendingGenderKey = null;
        pendingVendorId = null;
        vendorBrowseMode = 'products';
        browseNeedsService = true;
        browseBackUrl = jbwHomeUrl;
        document.getElementById('jbwServicesModal').style.display = 'none';
        document.getElementById('jbwSubcategoryModal').style.display = 'none';
        document.getElementById('jbwGenderModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function openServiceBrowse(serviceId) {
        pendingVendorId = null;
        vendorBrowseMode = 'products';
        browseNeedsService = false;
        pendingGenderKey = null;
        browseBackUrl = jbwServicesUrl;
        openGenderModal(serviceId);
    }

    function openVendorProducts(vendorId) {
        pendingVendorId = vendorId ? Number(vendorId) : null;
        vendorBrowseMode = 'products';
        currentServiceId = null;
        pendingGenderKey = null;
        browseNeedsService = false;
        browseBackUrl = jbwHomeUrl;
        document.getElementById('jbwGenderModal').style.display = 'none';
        document.getElementById('jbwSubcategoryModal').style.display = 'none';
        document.getElementById('jbwServicesModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function openVendorPortfolio(vendorId) {
        pendingVendorId = vendorId ? Number(vendorId) : null;
        vendorBrowseMode = 'portfolio';
        currentServiceId = null;
        pendingGenderKey = null;
        browseNeedsService = false;
        browseBackUrl = jbwHomeUrl;
        document.getElementById('jbwGenderModal').style.display = 'none';
        document.getElementById('jbwSubcategoryModal').style.display = 'none';
        document.getElementById('jbwServicesModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function openGenderModal(serviceId) {
        currentServiceId = serviceId ? Number(serviceId) : null;
        pendingGenderKey = null;
        browseNeedsService = false;
        document.getElementById('jbwServicesModal').style.display = 'none';
        document.getElementById('jbwSubcategoryModal').style.display = 'none';
        document.getElementById('jbwGenderModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeGenderModal() {
        document.getElementById('jbwGenderModal').style.display = 'none';
        unlockBodyIfNoModals();
    }

    function setBrowsePanelVisible(panel, visible) {
        if (!panel) {
            return;
        }
        panel.hidden = !visible;
        panel.style.display = visible ? '' : 'none';
    }

    function showSubcategoryEmptyState(title) {
        document.getElementById('jbwSubcategoryTitle').textContent = title;
        const grid = document.getElementById('jbwSubcategoryGrid');
        const empty = document.getElementById('jbwSubcategoryEmpty');
        grid.innerHTML = '';
        setBrowsePanelVisible(grid, false);
        setBrowsePanelVisible(empty, true);
        document.getElementById('jbwSubcategoryModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function renderSubcategoryGrid(main, subs) {
        document.getElementById('jbwSubcategoryTitle').textContent = main.name;
        const grid = document.getElementById('jbwSubcategoryGrid');
        const empty = document.getElementById('jbwSubcategoryEmpty');
        grid.innerHTML = '';
        setBrowsePanelVisible(empty, false);
        setBrowsePanelVisible(grid, true);

        subs.forEach((sub) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'jbw-subcat-card';
            button.onclick = () => selectSubcategory(sub.id);

            const media = document.createElement('div');
            media.className = 'jbw-subcat-card-media';
            const img = document.createElement('img');
            img.src = sub.image;
            img.alt = sub.name;
            img.loading = 'lazy';
            media.appendChild(img);

            const label = document.createElement('span');
            label.className = 'jbw-subcat-card-label';
            label.textContent = sub.name;

            button.appendChild(media);
            button.appendChild(label);
            grid.appendChild(button);
        });

        document.getElementById('jbwSubcategoryModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function syncServicesEmptyState() {
        const grid = document.querySelector('#jbwServicesModal .jbw-modal-options-grid');
        const empty = document.getElementById('jbwServicesEmpty');
        if (!grid || !empty) {
            return;
        }

        const visibleCount = Array.from(grid.querySelectorAll('.jbw-service-option'))
            .filter((button) => button.style.display !== 'none' && !button.hidden)
            .length;

        const showEmpty = grid.querySelectorAll('.jbw-service-option').length > 0 && visibleCount === 0;
        setBrowsePanelVisible(empty, showEmpty);
        setBrowsePanelVisible(grid, !showEmpty);
    }

    function syncServiceOptionsForGender(genderKey) {
        const key = String(genderKey || '').toLowerCase();
        const main = jbwSubcategoryTree[key];
        const allowed = new Set(
            (main?.subs || [])
                .map((sub) => Number(sub.service_category_id))
                .filter((id) => id > 0)
        );
        const hasAllowed = allowed.size > 0;

        document.querySelectorAll('#jbwServicesModal .jbw-service-option').forEach((button) => {
            const serviceId = Number(button.dataset.serviceId || 0);
            const visible = !hasAllowed || allowed.has(serviceId);
            button.hidden = !visible;
            button.style.display = visible ? '' : 'none';
        });

        syncServicesEmptyState();
    }

    function openServicesModal(genderKey) {
        pendingGenderKey = String(genderKey || '').toLowerCase();
        browseNeedsService = false;
        browseBackUrl = jbwHomeUrl;

        const main = jbwSubcategoryTree[pendingGenderKey];
        if (main && (!Array.isArray(main.subs) || !main.subs.length)) {
            currentMainCategory = main;
            closeGenderModal();
            closeServicesModal();
            showSubcategoryEmptyState(main.name);
            return;
        }

        syncServiceOptionsForGender(pendingGenderKey);
        document.getElementById('jbwGenderModal').style.display = 'none';
        document.getElementById('jbwSubcategoryModal').style.display = 'none';
        document.getElementById('jbwServicesModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeServicesModal() {
        document.getElementById('jbwServicesModal').style.display = 'none';
        unlockBodyIfNoModals();
    }

    function selectService(serviceId) {
        currentServiceId = serviceId ? Number(serviceId) : null;
        browseNeedsService = false;
        const genderKey = pendingGenderKey;
        closeServicesModal();

        if (genderKey) {
            selectGender(genderKey);
            return;
        }

        openGenderModal(currentServiceId);
    }

    function closeSubcategoryModal() {
        document.getElementById('jbwSubcategoryModal').style.display = 'none';
        document.body.style.overflow = '';
        currentMainCategory = null;
        const grid = document.getElementById('jbwSubcategoryGrid');
        const empty = document.getElementById('jbwSubcategoryEmpty');
        setBrowsePanelVisible(grid, true);
        setBrowsePanelVisible(empty, false);
    }

    function selectGender(selectedGenderKey) {
        const key = String(selectedGenderKey || '').toLowerCase();
        const main = jbwSubcategoryTree[key];

        if (!main) {
            if (vendorBrowseMode === 'portfolio' && goToVendorPortfolio({ service: currentServiceId })) {
                return;
            }
            const params = catalogParams();
            window.location.href = jbwCatalogBaseUrl + (params.toString() ? '?' + params.toString() : '');
            return;
        }

        if (browseNeedsService && !currentServiceId) {
            pendingGenderKey = key;
            closeGenderModal();
            document.getElementById('jbwServicesModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            return;
        }

        currentMainCategory = main;
        closeGenderModal();
        closeServicesModal();

        let subs = Array.isArray(main.subs) ? main.subs.slice() : [];
        if (currentServiceId) {
            subs = subs.filter((sub) => Number(sub.service_category_id) === Number(currentServiceId));
        }

        if (!subs.length) {
            if (vendorBrowseMode === 'portfolio' && goToVendorPortfolio({
                category: main.id,
                service: currentServiceId,
            })) {
                return;
            }

            showSubcategoryEmptyState(main.name);
            return;
        }

        renderSubcategoryGrid(main, subs);
    }

    function selectSubcategory(subcategoryId) {
        if (!currentMainCategory) {
            return;
        }

        if (vendorBrowseMode === 'portfolio' && goToVendorPortfolio({
            category: currentMainCategory.id,
            subcategory: subcategoryId,
            service: currentServiceId,
        })) {
            return;
        }

        const params = catalogParams({
            category: currentMainCategory.id,
            subcategory: subcategoryId,
            service: currentServiceId,
        });

        window.location.href = jbwCatalogBaseUrl + '?' + params.toString();
    }

    window.addEventListener('click', function (event) {
        if (event.target === document.getElementById('jbwGenderModal')) {
            closeGenderModal();
        }
        if (event.target === document.getElementById('jbwServicesModal')) {
            closeServicesModal();
        }
        if (event.target === document.getElementById('jbwSubcategoryModal')) {
            closeSubcategoryModal();
        }
    });
</script>
