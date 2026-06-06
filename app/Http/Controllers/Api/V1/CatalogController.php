<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Category;
use App\Models\PortfolioItem;
use App\Support\Api\CustomerApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = PortfolioItem::query()
            ->with(['vendor', 'category'])
            ->whereIn('status', ['approved', 'pending']);

        if ($request->filled('search')) {
            $term = '%'.$request->string('search').'%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhereHas('vendor', fn ($vendor) => $vendor->where('brand_name', 'like', $term));
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->integer('vendor_id'));
        }

        if ($request->filled('service')) {
            $service = $request->string('service');
            $query->whereHas('category', function ($category) use ($service) {
                $category->where('slug', $service)->orWhere('name', 'like', '%'.$service.'%');
            });
        }

        $items = $query->latest('id')->paginate($request->integer('per_page', 12));

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return $this->success([
            ...CustomerApiPresenter::paginator($items, fn (PortfolioItem $item) => CustomerApiPresenter::catalogItem($item)),
            'filters' => [
                'categories' => $categories->map(fn ($category) => CustomerApiPresenter::category($category))->values()->all(),
            ],
        ]);
    }

    public function show(PortfolioItem $item): JsonResponse
    {
        abort_unless($item->isApprovedForCatalog(), 404);

        $item->load(['vendor', 'category']);

        $related = PortfolioItem::query()
            ->with(['vendor', 'category'])
            ->where('vendor_id', $item->vendor_id)
            ->where('id', '!=', $item->id)
            ->whereIn('status', ['approved', 'pending'])
            ->limit(4)
            ->get();

        return $this->success(CustomerApiPresenter::catalogDetail($item, $related));
    }
}
