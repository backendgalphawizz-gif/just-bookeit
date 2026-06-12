<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Vendor;
use App\Support\Api\CatalogFilter;
use App\Support\Api\CustomerApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DesignerController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Vendor::query()->active()->where('is_listing_active', true);

        if ($request->filled('search')) {
            $term = '%'.$request->string('search').'%';
            $query->where(function ($q) use ($term) {
                $q->where('brand_name', 'like', $term)
                    ->orWhere('shop_name', 'like', $term)
                    ->orWhere('city', 'like', $term);
            });
        }

        if ($request->boolean('featured')) {
            $query->orderByDesc('rating');
        } else {
            $query->orderBy('brand_name');
        }

        $designers = $query->paginate($request->integer('per_page', 12));

        return $this->success(
            CustomerApiPresenter::paginator($designers, fn (Vendor $vendor) => CustomerApiPresenter::designerSummary($vendor))
        );
    }

    public function show(Request $request, Vendor $designer): JsonResponse
    {
        abort_unless($designer->status === 'active', 404);

        $request->validate(CatalogFilter::validationRules());

        $query = $designer->portfolioItems()
            ->with(['vendor', 'category']);

        CatalogFilter::applyToQuery($query, $request);

        $portfolio = $query->latest('id')->paginate($request->integer('per_page', 12));

        return $this->success([
            ...CustomerApiPresenter::designerDetail($designer, collect($portfolio->items())),
            'products_pagination' => [
                'current_page' => $portfolio->currentPage(),
                'last_page' => $portfolio->lastPage(),
                'per_page' => $portfolio->perPage(),
                'total' => $portfolio->total(),
                'has_more' => $portfolio->hasMorePages(),
            ],
            'filters' => [
                'applied' => CatalogFilter::applied($request),
            ],
        ]);
    }
}
