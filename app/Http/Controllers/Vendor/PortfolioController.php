<?php

namespace App\Http\Controllers\Vendor;

use App\Models\VendorPortfolioImage;
use App\Support\StoresUploadedFiles;
use App\Support\VendorValidationRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortfolioController extends VendorController
{
    public function index(): View
    {
        $vendor = $this->vendor();
        $portfolioByAudience = [];

        foreach (['women' => 'Women', 'men' => 'Men', 'kids' => 'Kids'] as $key => $label) {
            $portfolioByAudience[$key] = [
                'label' => $label,
                'images' => VendorPortfolioImage::query()
                    ->where('vendor_id', $vendor->id)
                    ->where('audience', $key)
                    ->orderBy('sort_order')
                    ->orderByDesc('id')
                    ->get(),
            ];
        }

        $photoCount = collect($portfolioByAudience)->sum(fn (array $group) => $group['images']->count());

        return view('vendor.portfolio.index', compact('portfolioByAudience', 'photoCount', 'vendor'));
    }

    public function store(Request $request): RedirectResponse
    {
        $vendor = $this->vendor();
        $data = $this->validateVendor($request, VendorValidationRules::portfolioUpload());

        $imagePath = StoresUploadedFiles::store($request->file('portfolio_image'), 'vendors/portfolio');

        VendorPortfolioImage::query()->create([
            'vendor_id' => $vendor->id,
            'audience' => $data['audience'],
            'image_path' => $imagePath,
            'sort_order' => (int) VendorPortfolioImage::query()
                ->where('vendor_id', $vendor->id)
                ->where('audience', $data['audience'])
                ->max('sort_order') + 1,
        ]);

        return redirect()
            ->route('vendor.portfolio.index', ['audience' => $data['audience']])
            ->with('success', 'Portfolio image added.');
    }

    public function destroy(VendorPortfolioImage $portfolioImage): RedirectResponse
    {
        abort_unless($portfolioImage->vendor_id === $this->vendor()->id, 403);

        $audience = $portfolioImage->audience;
        StoresUploadedFiles::delete($portfolioImage->image_path);
        $portfolioImage->delete();

        return redirect()
            ->route('vendor.portfolio.index', ['audience' => $audience])
            ->with('success', 'Portfolio image removed.');
    }
}
