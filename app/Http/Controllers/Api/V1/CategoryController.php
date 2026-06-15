<?php

namespace App\Http\Controllers\Api\V1;

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
                'categories' => $categories->map(fn ($category) => CustomerApiPresenter::category($category, includeSubcategories: true))->values()->all(),
                'services' => $services->map(fn ($category) => CustomerApiPresenter::category($category))->values()->all(),
            ]);
        }

        $query = Category::query()->active();

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->integer('parent_id'));
        }

        $categories = $query->orderBy('sort_order')->orderBy('name')->get();

        return $this->success([
            'items' => $categories->map(fn ($category) => CustomerApiPresenter::category($category))->values()->all(),
        ]);
    }
}
