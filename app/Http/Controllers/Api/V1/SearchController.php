<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\PortfolioItem;
use App\Models\Vendor;
use App\Support\Api\CustomerApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:100'],
        ]);

        $term = '%'.$request->string('q').'%';

        $items = PortfolioItem::query()
            ->with(['vendor', 'category'])
            ->where('status', 'approved')
            ->whereHas('vendor', fn ($vendor) => $vendor->where('status', 'active')->where('is_listing_active', true))
            ->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term);
            })
            ->latest('id')
            ->limit(10)
            ->get();

        $designers = Vendor::query()
            ->active()
            ->where(function ($q) use ($term) {
                $q->where('brand_name', 'like', $term)
                    ->orWhere('shop_name', 'like', $term)
                    ->orWhere('city', 'like', $term);
            })
            ->orderByDesc('rating')
            ->limit(6)
            ->get();

        return $this->success([
            'query' => $request->string('q'),
            'catalog_items' => $items->map(fn ($item) => CustomerApiPresenter::catalogItem($item))->values()->all(),
            'designers' => $designers->map(fn ($vendor) => CustomerApiPresenter::designerSummary($vendor))->values()->all(),
        ]);
    }
}
