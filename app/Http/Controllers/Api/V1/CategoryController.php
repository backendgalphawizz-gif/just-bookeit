<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Category;
use App\Support\Api\CatalogFilter;
use App\Support\Api\CustomerApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $serviceCategoryId = CatalogFilter::resolveServiceCategoryId($request);
        $mainCategoryId = CatalogFilter::resolveMainCategoryId($request)
            ?? ($request->filled('parent_id') ? $request->integer('parent_id') : null);

        if ($request->boolean('roots')) {
            $subQuery = fn ($query) => $query
                ->with('serviceCategory')
                ->active()
                ->when(
                    $serviceCategoryId,
                    fn ($q) => CatalogFilter::applySubcategoryServiceFilter($q, $serviceCategoryId)
                )
                ->orderBy('sort_order')
                ->orderBy('name');

            $categoriesQuery = Category::query()
                ->active()
                ->main()
                ->whereNull('parent_id')
                ->when($mainCategoryId, fn ($q) => $q->where('id', $mainCategoryId))
                ->with(['subcategories' => $subQuery])
                ->orderBy('sort_order')
                ->orderBy('name');

            $categories = $categoriesQuery->get();

            $services = Category::query()
                ->active()
                ->service()
                ->when($serviceCategoryId, fn ($q) => $q->where('id', $serviceCategoryId))
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            return $this->success([
                'categories' => $categories->map(fn ($category) => CustomerApiPresenter::category($category, includeSubcategories: true))->values()->all(),
                'services' => $services->map(fn ($category) => CustomerApiPresenter::category($category))->values()->all(),
                'filters' => array_filter([
                    'category_id' => $mainCategoryId,
                    'parent_id' => $mainCategoryId,
                    'service_category_id' => $serviceCategoryId,
                ]),
            ]);
        }

        $query = Category::query()->active();

        // Combined shop category + service category → subcategories matching both.
        $filteringByShopOrService = $mainCategoryId || $serviceCategoryId;

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        } elseif ($filteringByShopOrService) {
            $query->sub();
        }

        if ($mainCategoryId) {
            $query->where('parent_id', $mainCategoryId);
        } elseif ($request->filled('parent_id')) {
            $query->where('parent_id', $request->integer('parent_id'));
        }

        if ($serviceCategoryId) {
            CatalogFilter::applySubcategoryServiceFilter($query, $serviceCategoryId);
        }

        $categories = $query->with('serviceCategory')->orderBy('sort_order')->orderBy('name')->get();

        return $this->success([
            'items' => $categories->map(fn ($category) => CustomerApiPresenter::category($category))->values()->all(),
            'filters' => array_filter([
                'type' => $request->filled('type') ? $request->string('type')->toString() : ($filteringByShopOrService ? 'sub' : null),
                'category_id' => $mainCategoryId,
                'parent_id' => $mainCategoryId ?? ($request->filled('parent_id') ? $request->integer('parent_id') : null),
                'service_category_id' => $serviceCategoryId,
                'service' => $request->filled('service') ? $request->string('service')->toString() : null,
            ]),
        ]);
    }
}
