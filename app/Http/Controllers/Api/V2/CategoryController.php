<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\ApiController;
use App\Models\Category;
use App\Support\Api\CatalogFilter;
use App\Support\Api\CustomerApiPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        if ($request->boolean('roots')) {
            return $this->roots($request);
        }

        if ($this->wantsFilteredFlatList($request)) {
            return $this->filtered($request);
        }

        return $this->productCatalog($request);
    }

    public function subcategories(Request $request, Category $category): JsonResponse
    {
        if (! $category->isMain() || ! $category->is_active) {
            return $this->error('Category not found.', 404);
        }

        $serviceCategoryId = $this->resolveServiceCategoryFilter($request);

        $subcategories = Category::query()
            ->active()
            ->sub()
            ->where('parent_id', $category->id)
            ->when($serviceCategoryId, fn (Builder $q) => CatalogFilter::applySubcategoryServiceFilter($q, $serviceCategoryId))
            ->with('serviceCategory')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $this->success([
            'category' => CustomerApiPresenter::category($category),
            'subcategories' => $subcategories
                ->map(fn (Category $subcategory) => CustomerApiPresenter::category($subcategory))
                ->values()
                ->all(),
            'applied' => $this->appliedServiceFilter($serviceCategoryId),
        ]);
    }

    protected function productCatalog(Request $request): JsonResponse
    {
        $includeSubcategories = $request->boolean('include_subcategories', true);
        $serviceCategoryId = $this->resolveServiceCategoryFilter($request);

        $categoriesQuery = Category::query()
            ->active()
            ->main()
            ->when($includeSubcategories && $serviceCategoryId, function ($query) use ($serviceCategoryId) {
                $query->whereHas('subcategories', function (Builder $sub) use ($serviceCategoryId) {
                    $sub->active();
                    CatalogFilter::applySubcategoryServiceFilter($sub, $serviceCategoryId);
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($includeSubcategories) {
            $categoriesQuery->with([
                'subcategories' => function ($query) use ($serviceCategoryId) {
                    $query
                        ->with('serviceCategory')
                        ->active()
                        ->when($serviceCategoryId, fn (Builder $sub) => CatalogFilter::applySubcategoryServiceFilter($sub, $serviceCategoryId))
                        ->orderBy('sort_order')
                        ->orderBy('name');
                },
            ]);
        }

        $categories = $categoriesQuery->get();

        $serviceCategoriesQuery = Category::query()
            ->active()
            ->service()
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($serviceCategoryId) {
            $serviceCategoriesQuery->where('id', $serviceCategoryId);
        }

        $serviceCategories = $serviceCategoriesQuery->get();

        return $this->success([
            'categories' => $categories
                ->map(fn (Category $category) => CustomerApiPresenter::category($category, $includeSubcategories))
                ->values()
                ->all(),
            'service_categories' => $serviceCategories
                ->map(fn (Category $category) => CustomerApiPresenter::category($category))
                ->values()
                ->all(),
            'applied' => $this->appliedServiceFilter($serviceCategoryId),
        ]);
    }

    protected function roots(Request $request): JsonResponse
    {
        $serviceCategoryId = $this->resolveServiceCategoryFilter($request);

        $categoriesQuery = Category::query()
            ->active()
            ->main()
            ->whereNull('parent_id')
            ->when($serviceCategoryId, function ($query) use ($serviceCategoryId) {
                $query->whereHas('subcategories', function (Builder $sub) use ($serviceCategoryId) {
                    $sub->active();
                    CatalogFilter::applySubcategoryServiceFilter($sub, $serviceCategoryId);
                });
            })
            ->with([
                'subcategories' => function ($query) use ($serviceCategoryId) {
                    $query
                        ->with('serviceCategory')
                        ->active()
                        ->when($serviceCategoryId, fn (Builder $sub) => CatalogFilter::applySubcategoryServiceFilter($sub, $serviceCategoryId))
                        ->orderBy('sort_order')
                        ->orderBy('name');
                },
            ])
            ->orderBy('sort_order')
            ->orderBy('name');

        $categories = $categoriesQuery->get();

        $servicesQuery = Category::query()
            ->active()
            ->service()
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($serviceCategoryId) {
            $servicesQuery->where('id', $serviceCategoryId);
        }

        $services = $servicesQuery->get();

        return $this->success([
            'categories' => $categories
                ->map(fn (Category $category) => CustomerApiPresenter::category($category, includeSubcategories: true))
                ->values()
                ->all(),
            'services' => $services
                ->map(fn (Category $category) => CustomerApiPresenter::category($category))
                ->values()
                ->all(),
            'applied' => $this->appliedServiceFilter($serviceCategoryId),
        ]);
    }

    protected function filtered(Request $request): JsonResponse
    {
        $serviceCategoryId = $this->resolveServiceCategoryFilter($request);

        $query = Category::query()->active();

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->toString());
        }

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->integer('parent_id'));
        }

        if ($serviceCategoryId) {
            CatalogFilter::applySubcategoryServiceFilter($query, $serviceCategoryId);
        }

        $categories = $query->with('serviceCategory')->orderBy('sort_order')->orderBy('name')->get();

        return $this->success([
            'items' => $categories
                ->map(fn (Category $category) => CustomerApiPresenter::category($category))
                ->values()
                ->all(),
            'applied' => $this->appliedServiceFilter($serviceCategoryId),
        ]);
    }

    protected function wantsFilteredFlatList(Request $request): bool
    {
        if ($request->filled('parent_id')) {
            return true;
        }

        if (! $request->filled('type')) {
            return false;
        }

        $type = $request->string('type')->toString();

        return in_array($type, [Category::TYPE_MAIN, Category::TYPE_SUB, Category::TYPE_SERVICE], true);
    }

    protected function resolveServiceCategoryFilter(Request $request): ?int
    {
        if ($request->filled('service')) {
            return CatalogFilter::resolveServiceCategoryId($request);
        }

        if ($request->filled('service_category_id') || $request->filled('service_id')) {
            return CatalogFilter::resolveServiceCategoryId($request);
        }

        if ($request->filled('type') && ! $this->wantsFilteredFlatList($request)) {
            $slug = CatalogFilter::normalizeServiceSlug($request->string('type')->toString());

            if ($slug !== null) {
                return Category::query()
                    ->active()
                    ->service()
                    ->where('slug', $slug)
                    ->value('id');
            }
        }

        return null;
    }

    /** @return array<string, int|string>|array{} */
    protected function appliedServiceFilter(?int $serviceCategoryId): array
    {
        if (! $serviceCategoryId) {
            return [];
        }

        $serviceCategory = Category::query()->find($serviceCategoryId);

        if (! $serviceCategory) {
            return [];
        }

        $applied = [
            'service_category_id' => $serviceCategory->id,
            'service' => $serviceCategory->slug,
            'service_name' => $serviceCategory->name,
        ];

        if ($serviceCategory->slug === 'fashion-designer') {
            $applied['includes_dress_subcategories'] = true;
        }

        return $applied;
    }
}
