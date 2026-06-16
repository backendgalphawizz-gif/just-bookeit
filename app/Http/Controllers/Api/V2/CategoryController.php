<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\ApiController;
use App\Models\Category;
use App\Support\Api\CustomerApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        if ($request->boolean('roots')) {
            return $this->roots();
        }

        if ($request->filled('type') || $request->filled('parent_id')) {
            return $this->filtered($request);
        }

        return $this->productCatalog($request);
    }

    public function subcategories(Category $category): JsonResponse
    {
        if (! $category->isMain() || ! $category->is_active) {
            return $this->error('Category not found.', 404);
        }

        $subcategories = Category::query()
            ->active()
            ->sub()
            ->where('parent_id', $category->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $this->success([
            'category' => CustomerApiPresenter::category($category),
            'subcategories' => $subcategories
                ->map(fn (Category $subcategory) => CustomerApiPresenter::category($subcategory))
                ->values()
                ->all(),
        ]);
    }

    protected function productCatalog(Request $request): JsonResponse
    {
        $includeSubcategories = $request->boolean('include_subcategories', true);

        $categoriesQuery = Category::query()
            ->active()
            ->main()
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($includeSubcategories) {
            $categoriesQuery->with([
                'subcategories' => fn ($query) => $query->active()->orderBy('sort_order')->orderBy('name'),
            ]);
        }

        $categories = $categoriesQuery->get();

        $serviceCategories = Category::query()
            ->active()
            ->service()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $this->success([
            'categories' => $categories
                ->map(fn (Category $category) => CustomerApiPresenter::category($category, $includeSubcategories))
                ->values()
                ->all(),
            'service_categories' => $serviceCategories
                ->map(fn (Category $category) => CustomerApiPresenter::category($category))
                ->values()
                ->all(),
        ]);
    }

    protected function roots(): JsonResponse
    {
        $categories = Category::query()
            ->active()
            ->main()
            ->whereNull('parent_id')
            ->with(['subcategories' => fn ($query) => $query->active()->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $services = Category::query()
            ->active()
            ->service()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $this->success([
            'categories' => $categories
                ->map(fn (Category $category) => CustomerApiPresenter::category($category, includeSubcategories: true))
                ->values()
                ->all(),
            'services' => $services
                ->map(fn (Category $category) => CustomerApiPresenter::category($category))
                ->values()
                ->all(),
        ]);
    }

    protected function filtered(Request $request): JsonResponse
    {
        $query = Category::query()->active();

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->toString());
        }

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->integer('parent_id'));
        }

        $categories = $query->orderBy('sort_order')->orderBy('name')->get();

        return $this->success([
            'items' => $categories
                ->map(fn (Category $category) => CustomerApiPresenter::category($category))
                ->values()
                ->all(),
        ]);
    }
}
