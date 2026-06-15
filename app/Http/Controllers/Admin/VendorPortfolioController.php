<?php

namespace App\Http\Controllers\Admin;

use App\Models\Vendor;
use App\Models\VendorPortfolioImage;
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

        $portfolioFilter = $this->portfolioImageFilter($request);

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
            ->whereHas('portfolioImages', $portfolioFilter)
            ->withCount(['portfolioImages as portfolio_photos_count' => $portfolioFilter])
            ->orderBy('brand_name')
            ->paginate(15)
            ->withQueryString();

        $vendorOptions = Vendor::query()->orderBy('brand_name')->get(['id', 'brand_name']);

        return view('admin.vendor-portfolio.index', compact('vendors', 'vendorOptions'));
    }

    public function show(Request $request, Vendor $vendor): View
    {
        $this->validateListDateRange($request);

        $portfolioFilter = $this->portfolioImageFilter($request);

        $portfolioByAudience = [];

        foreach (['women' => 'Women', 'men' => 'Men', 'kids' => 'Kids'] as $key => $label) {
            $query = VendorPortfolioImage::query()
                ->where('vendor_id', $vendor->id)
                ->where('audience', $key);

            $portfolioFilter($query);

            $portfolioByAudience[$key] = [
                'label' => $label,
                'images' => $query->orderBy('sort_order')->orderByDesc('id')->get(),
            ];
        }

        $photoCount = collect($portfolioByAudience)->sum(fn (array $group) => $group['images']->count());
        $totalPhotoCount = $vendor->portfolioImages()->count();

        return view('admin.vendor-portfolio.show', compact(
            'vendor',
            'portfolioByAudience',
            'photoCount',
            'totalPhotoCount',
        ));
    }

    /** @return \Closure(HasMany|Builder): void */
    protected function portfolioImageFilter(Request $request): \Closure
    {
        return function (HasMany|Builder $query) use ($request): void {
            $this->applyDateRange($query, $request);

            if ($request->filled('audience')) {
                $query->where('audience', $request->string('audience'));
            }
        };
    }
}
