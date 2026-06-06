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
        $query = Category::query()->where('is_active', true);

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->integer('parent_id'));
        } elseif ($request->boolean('roots')) {
            $query->whereNull('parent_id');
        }

        $categories = $query->orderBy('sort_order')->orderBy('name')->get();

        return $this->success([
            'items' => $categories->map(fn ($category) => CustomerApiPresenter::category($category))->values()->all(),
        ]);
    }
}
