<?php

namespace App\Http\Controllers\Admin;

use App\Models\PortfolioItem;
use App\Models\Vendor;
use App\Support\AdminCityScope;
use App\Support\AppliesListDateFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorPortfolioController extends AdminController
{
    use AppliesListDateFilter;

    protected string $permissionModule = 'portfolio';

    public function index(Request $request): View
    {
        $this->validateListDateRange($request);

        $productFilter = $this->productImageFilter($request);

        $vendors = AdminCityScope::scopeVendors(Vendor::query())
            ->when($request->filled('vendor_id'), fn ($q) => $q->where('id', $request->integer('vendor_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($q) use ($term) {
                    $q->where('brand_name', 'like', $term)
                        ->orWhere('owner_name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('mobile', 'like', $term)
                        ->orWhere('business_mobile', 'like', $term)
                        ->orWhere('vendor_code', 'like', $term);
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('city'), fn ($q) => $q->where('city', 'like', '%'.$request->string('city').'%'))
            ->whereHas('portfolioItems', $productFilter)
            ->withCount(['portfolioItems as product_photos_count' => $productFilter])
            ->newestFirst()
            ->paginate(15)
            ->withQueryString();

        $vendorOptions = Vendor::query()->orderBy('brand_name')->get(['id', 'brand_name']);

        return view('admin.vendor-portfolio.index', compact('vendors', 'vendorOptions'));
    }

    public function show(Request $request, Vendor $vendor): View
    {
        $this->authorizeVendorCity($vendor);

        $this->validateListDateRange($request);

        $productFilter = $this->productImageFilter($request);

        $productsQuery = PortfolioItem::query()
            ->where('vendor_id', $vendor->id)
            ->with(['images', 'category']);

        $productFilter($productsQuery);

        $products = $productsQuery
            ->newestFirst()
            ->get();

        $portfolioByAudience = [];

        foreach (['women' => 'Women', 'men' => 'Men', 'kids' => 'Kids'] as $key => $label) {
            $portfolioByAudience[$key] = [
                'label' => $label,
                'images' => collect(),
            ];
        }

        foreach ($products as $product) {
            $audience = in_array($product->audience, ['women', 'men', 'kids'], true)
                ? $product->audience
                : 'women';

            foreach ($this->productImageEntries($product) as $entry) {
                $portfolioByAudience[$audience]['images']->push($entry);
            }
        }

        $photoCount = collect($portfolioByAudience)->sum(fn (array $group) => $group['images']->count());
        $totalPhotoCount = $this->vendorProductImageCount($vendor);

        return view('admin.vendor-portfolio.show', compact(
            'vendor',
            'portfolioByAudience',
            'photoCount',
            'totalPhotoCount',
        ));
    }

    /** @return \Closure(HasMany|Builder): void */
    protected function productImageFilter(Request $request): \Closure
    {
        return function (HasMany|Builder $query) use ($request): void {
            $this->applyDateRange($query, $request);

            if ($request->filled('audience')) {
                $query->where('audience', $request->string('audience'));
            }

            $query->where(function (Builder $q) {
                $q->where(function (Builder $primary) {
                    $primary->whereNotNull('image_url')->where('image_url', '!=', '');
                })->orWhereHas('images', function (Builder $images) {
                    $images->where(function (Builder $media) {
                        $media->whereNull('media_type')
                            ->orWhere('media_type', 'image');
                    });
                });
            });
        };
    }

    /**
     * @return list<array{url: string, title: string, product_id: int, created_at: mixed}>
     */
    protected function productImageEntries(PortfolioItem $product): array
    {
        $entries = [];
        $seen = [];

        foreach ($product->galleryMediaItems() as $media) {
            if (($media['type'] ?? '') !== 'image') {
                continue;
            }

            $url = $media['url'] ?? null;
            if (! is_string($url) || $url === '') {
                continue;
            }

            $key = ltrim((string) (parse_url($url, PHP_URL_PATH) ?: $url), '/');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $entries[] = [
                'url' => $url,
                'title' => $product->title,
                'product_id' => $product->id,
                'created_at' => $product->created_at,
            ];
        }

        return $entries;
    }

    protected function vendorProductImageCount(Vendor $vendor): int
    {
        $products = PortfolioItem::query()
            ->where('vendor_id', $vendor->id)
            ->with('images')
            ->get();

        return $products->sum(fn (PortfolioItem $product) => count($this->productImageEntries($product)));
    }
}
