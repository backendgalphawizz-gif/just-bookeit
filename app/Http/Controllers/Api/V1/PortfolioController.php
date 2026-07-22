<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\PortfolioItem;
use App\Models\Vendor;
use App\Models\VendorPortfolioImage;
use App\Support\Api\CatalogFilter;
use App\Support\Api\CustomerApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortfolioController extends ApiController
{
    /**
     * Vendor portfolio / product images for the customer app.
     *
     * GET /api/v1/portfolio?vendor_id=1
     * Optional: service_category_id|service, category_id|shop_category_id|parent_id,
     *           subcategory_id, audience, page, per_page
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate(array_merge([
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'audience' => ['nullable', 'string', 'in:women,men,kids'],
            'sub_category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ], CatalogFilter::validationRules()));

        $vendor = Vendor::query()
            ->active()
            ->where('is_listing_active', true)
            ->findOrFail($request->integer('vendor_id'));

        $serviceCategoryId = CatalogFilter::resolveServiceCategoryId($request);
        $mainCategoryId = CatalogFilter::resolveMainCategoryId($request)
            ?? ($request->filled('parent_id') ? $request->integer('parent_id') : null);

        // Prefer explicit subcategory_id / sub_category_id from the app.
        $subcategoryId = null;
        if ($request->filled('subcategory_id')) {
            $subcategoryId = $request->integer('subcategory_id');
        } elseif ($request->filled('sub_category_id')) {
            $subcategoryId = $request->integer('sub_category_id');
        } else {
            $subcategoryId = CatalogFilter::resolveSubcategoryId($request);
        }

        $audience = $request->filled('audience')
            ? $request->string('audience')->toString()
            : CatalogFilter::resolveAudience($request);

        $productsQuery = PortfolioItem::query()
            ->with(['vendor', 'category', 'subcategory.parent', 'subcategory.serviceCategory', 'variants', 'images'])
            ->where('vendor_id', $vendor->id)
            ->where('status', 'approved')
            ->where(function ($query) {
                $query->where('is_listing_active', true)->orWhereNull('is_listing_active');
            });

        if ($serviceCategoryId) {
            $productsQuery->where('category_id', $serviceCategoryId);
        }

        if ($subcategoryId) {
            $productsQuery->where('subcategory_id', $subcategoryId);
        } elseif ($mainCategoryId) {
            $productsQuery->whereHas('subcategory', fn ($sub) => $sub->where('parent_id', $mainCategoryId));
        }

        if ($audience) {
            $productsQuery->where('audience', $audience);
        }

        $products = $productsQuery
            ->latest('id')
            ->paginate($request->integer('per_page', 20));

        $galleryQuery = VendorPortfolioImage::query()
            ->where('vendor_id', $vendor->id)
            ->orderBy('sort_order')
            ->orderByDesc('id');

        if ($audience) {
            $galleryQuery->where('audience', $audience);
        }

        $gallery = $galleryQuery->get();

        // Flatten product images for clients that only need an image list.
        $productImages = $products->getCollection()
            ->flatMap(function (PortfolioItem $item) {
                $payload = CustomerApiPresenter::catalogItem($item);
                $urls = collect([$payload['image_url'] ?? null])
                    ->merge($payload['gallery_image_urls'] ?? [])
                    ->filter()
                    ->unique()
                    ->values();

                return $urls->map(fn (string $url) => [
                    'product_id' => $item->id,
                    'title' => $item->title,
                    'image_url' => $url,
                    'service_category_id' => $item->category_id,
                    'subcategory_id' => $item->subcategory_id,
                    'audience' => $item->audience,
                ]);
            })
            ->values()
            ->all();

        return $this->success([
            'vendor' => CustomerApiPresenter::designerSummary($vendor),
            ...CustomerApiPresenter::paginator(
                $products,
                fn (PortfolioItem $item) => CustomerApiPresenter::catalogItem($item)
            ),
            'product_images' => $productImages,
            'profile_portfolio' => $gallery
                ->map(fn (VendorPortfolioImage $image) => CustomerApiPresenter::profilePortfolioImage($image))
                ->values()
                ->all(),
            'filters' => array_filter([
                'vendor_id' => $vendor->id,
                'service_category_id' => $serviceCategoryId,
                'category_id' => $mainCategoryId,
                'parent_id' => $mainCategoryId,
                'subcategory_id' => $subcategoryId,
                'audience' => $audience,
                'service' => $request->filled('service') ? $request->string('service')->toString() : null,
            ]),
        ]);
    }
}
