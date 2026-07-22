<?php

namespace App\Http\Controllers\Web;

use App\Models\Category;
use App\Models\PortfolioItem;
use App\Support\Api\CatalogFilter;
use App\Support\ProductOptionCatalog;
use App\Support\WebLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends WebController
{
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->filled('service') && ! $request->filled('category') && ! $request->filled('subcategory')) {
            return redirect()->route('web.services.index', $request->only(['service', 'designer', 'city', 'search']));
        }

        return $this->renderBrowse($request, CatalogFilter::BROWSE_CATEGORIES);
    }

    public function services(Request $request): View
    {
        return $this->renderBrowse($request, CatalogFilter::BROWSE_SERVICES);
    }

    public function show(PortfolioItem $item): View
    {
        abort_unless($item->isCatalogAvailable(), 404);

        $item->load(['vendor', 'category', 'subcategory.parent', 'images', 'variants']);

        $related = PortfolioItem::query()
            ->where('vendor_id', $item->vendor_id)
            ->where('id', '!=', $item->id)
            ->where('status', 'approved')
            ->whereHas('vendor', fn ($vendor) => $vendor
                ->where('status', 'active')
                ->where('is_listing_active', true))
            ->limit(4)
            ->get();

        $reviews = collect();
        $reviewCount = 0;
        $averageRating = 0.0;

        if ($item->vendor) {
            $reviewCount = (int) $item->vendor->reviews()->count();
            if ($reviewCount > 0) {
                $averageRating = round((float) $item->vendor->reviews()->avg('rating'), 1);
                $reviews = $item->vendor->reviews()->with('customer')->latest('id')->limit(10)->get();
            }
        }

        return view('web.catalog.show', compact('item', 'related', 'reviews', 'reviewCount', 'averageRating'));
    }

    protected function renderBrowse(Request $request, string $browseMode): View
    {
        $filterRequest = $this->catalogFilterRequest($request, $browseMode);

        $query = PortfolioItem::query()
            ->with(['vendor', 'category', 'subcategory.parent', 'variants']);

        CatalogFilter::applyToQuery($query, $filterRequest, $browseMode);

        if ($request->filled('search') && ! $request->filled('designer')) {
            $term = '%'.$request->string('search').'%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhereHas('vendor', fn ($vendor) => $vendor->where('brand_name', 'like', $term));
            });
        }

        $items = $query->latest('id')->paginate(12)->withQueryString();

        $mainCategories = Category::query()
            ->active()
            ->main()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $serviceCategories = Category::query()
            ->active()
            ->service()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $subcategories = Category::query()
            ->active()
            ->sub()
            ->when($request->filled('category'), fn ($q) => $q->where('parent_id', $request->integer('category')))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $filterSizes = ProductOptionCatalog::sizeNames(true);
        $filterColors = ProductOptionCatalog::colorApiItems(true);
        $appliedFilters = CatalogFilter::applied($filterRequest, $browseMode);

        return view('web.catalog.index', compact(
            'items',
            'mainCategories',
            'subcategories',
            'serviceCategories',
            'filterSizes',
            'filterColors',
            'appliedFilters',
            'browseMode',
        ));
    }

    protected function catalogFilterRequest(Request $request, string $browseMode): Request
    {
        $query = $request->query();
        $query['browse'] = $browseMode;

        if ($request->filled('category') && ! isset($query['category_id'])) {
            $query['category_id'] = $request->integer('category');
        }

        if ($request->filled('subcategory') && ! isset($query['subcategory_id'])) {
            $query['subcategory_id'] = $request->integer('subcategory');
        }

        if ($request->filled('service') && is_numeric($request->input('service')) && ! isset($query['service_id'])) {
            $query['service_id'] = $request->integer('service');
        }

        if ($request->filled('vendor') && ! isset($query['vendor_id'])) {
            $query['vendor_id'] = $request->integer('vendor');
        }

        // Prefer explicit lat/lng from the request; otherwise use header/session location.
        // Products are filtered by vendor coordinates within the admin discovery radius.
        if (! isset($query['latitude'], $query['longitude'])) {
            $location = WebLocation::get($request);
            $coords = WebLocation::coordinates($location);

            if ($coords !== null) {
                $query['latitude'] = $coords['latitude'];
                $query['longitude'] = $coords['longitude'];

                // Persist resolved coordinates back into session for later requests.
                if (is_array($location) && (
                    ! isset($location['latitude'], $location['longitude'])
                    || ! is_numeric($location['latitude'])
                    || ! is_numeric($location['longitude'])
                )) {
                    $location['latitude'] = $coords['latitude'];
                    $location['longitude'] = $coords['longitude'];
                    WebLocation::put($request, $location);
                }
            }
        }

        // Sidebar city text filter remains optional and explicit.
        // Do not overwrite it with session city — radius uses lat/lng instead.

        return $request->duplicate(query: $query);
    }
}
