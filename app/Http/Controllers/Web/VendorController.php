<?php

namespace App\Http\Controllers\Web;

use App\Models\Category;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class VendorController extends WebController
{
    public function show(Vendor $vendor): View
    {
        abort_unless($vendor->status === 'active', 404);

        $offeredServices = $this->offeredServices($vendor);

        $reviewCount = (int) $vendor->reviews()->count();
        $averageRating = $reviewCount > 0
            ? round((float) $vendor->reviews()->avg('rating'), 1)
            : round((float) ($vendor->rating ?? 0), 1);
        $reviews = $vendor->reviews()
            ->with('customer')
            ->latest('id')
            ->limit(10)
            ->get();

        return view('web.vendors.show', compact(
            'vendor',
            'offeredServices',
            'reviews',
            'reviewCount',
            'averageRating'
        ));
    }

    public function portfolio(Request $request, Vendor $vendor): View
    {
        abort_unless($vendor->status === 'active', 404);

        $items = $this->filteredPortfolioQuery($request, $vendor)
            ->latest('id')
            ->limit(48)
            ->get();

        $images = $items->flatMap(function ($item) {
            $urls = collect($item->galleryMediaItems())
                ->filter(fn ($media) => ($media['type'] ?? '') === 'image')
                ->pluck('url')
                ->filter()
                ->values();

            if ($urls->isEmpty()) {
                $fallback = $item->displayImageUrl();
                if ($fallback) {
                    $urls = collect([$fallback]);
                }
            }

            return $urls->map(fn ($url) => [
                'url' => $url,
                'title' => $item->title,
                'href' => route('web.catalog.show', $item),
            ]);
        })->values();

        return view('web.vendors.portfolio', compact('vendor', 'images'));
    }

    protected function filteredPortfolioQuery(Request $request, Vendor $vendor): HasMany
    {
        $portfolioQuery = $vendor->portfolioItems()
            ->where('status', 'approved')
            ->with(['category', 'variants', 'images']);

        if ($request->filled('service') && is_numeric($request->input('service'))) {
            $portfolioQuery->where('category_id', $request->integer('service'));
        }

        if ($request->filled('subcategory') && is_numeric($request->input('subcategory'))) {
            $portfolioQuery->where('subcategory_id', $request->integer('subcategory'));
        } elseif ($request->filled('category') && is_numeric($request->input('category'))) {
            $mainCategoryId = $request->integer('category');
            $mainCategory = Category::query()->find($mainCategoryId);
            $audience = strtolower((string) ($mainCategory?->slug ?: $mainCategory?->name));

            $portfolioQuery->where(function ($query) use ($mainCategoryId, $audience) {
                $query->whereHas('subcategory', fn ($sub) => $sub->where('parent_id', $mainCategoryId));
                if (in_array($audience, ['women', 'men', 'kids'], true)) {
                    $query->orWhere('audience', $audience);
                }
            });
        }

        return $portfolioQuery;
    }

    /** @return Collection<int, Category> */
    protected function offeredServices(Vendor $vendor): Collection
    {
        $categoryIds = $vendor->portfolioItems()
            ->where('status', 'approved')
            ->distinct()
            ->pluck('category_id')
            ->filter()
            ->values();

        $offeredServices = Category::query()
            ->active()
            ->service()
            ->when(
                $categoryIds->isNotEmpty(),
                fn ($query) => $query->whereIn('id', $categoryIds),
                fn ($query) => $query->whereRaw('0 = 1')
            )
            ->orderBy('sort_order')
            ->get();

        if ($offeredServices->isEmpty()) {
            $selected = collect($vendor->selectedServiceTypes())->map(fn ($v) => strtolower(trim((string) $v)));
            $offeredServices = Category::query()
                ->active()
                ->service()
                ->orderBy('sort_order')
                ->get()
                ->filter(function (Category $service) use ($selected) {
                    if ($selected->isEmpty()) {
                        return true;
                    }

                    $haystack = strtolower($service->name.' '.$service->slug);

                    return $selected->contains(fn ($type) => str_contains($haystack, $type) || str_contains($type, (string) $service->slug));
                })
                ->values();
        }

        return $offeredServices;
    }
}
