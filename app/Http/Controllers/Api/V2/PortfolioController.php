<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\VendorPortfolioImage;
use App\Support\Api\VendorApiPresenter;
use App\Support\StoresUploadedFiles;
use App\Support\VendorValidationRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortfolioController extends VendorApiController
{
    public function index(Request $request): JsonResponse
    {
        $vendor = $this->vendor($request);

        $audience = $request->string('audience', 'women')->toString();
        if (! in_array($audience, ['women', 'men', 'kids'], true)) {
            $audience = 'women';
        }

        $images = VendorPortfolioImage::query()
            ->where('vendor_id', $vendor->id)
            ->where('audience', $audience)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        return $this->success([
            'audience' => $audience,
            'items' => $images->map(fn (VendorPortfolioImage $image) => VendorApiPresenter::portfolioImage($image))->values()->all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $vendor = $this->vendor($request);
        $data = $this->validateVendor($request, VendorValidationRules::portfolioUpload());

        $image = VendorPortfolioImage::query()->create([
            'vendor_id' => $vendor->id,
            'audience' => $data['audience'],
            'image_path' => StoresUploadedFiles::store($request->file('portfolio_image'), 'vendors/portfolio-gallery'),
            'sort_order' => VendorPortfolioImage::query()
                ->where('vendor_id', $vendor->id)
                ->where('audience', $data['audience'])
                ->count(),
        ]);

        return $this->success([
            'image' => VendorApiPresenter::portfolioImage($image),
        ], 'Portfolio image added.', 201);
    }

    public function destroy(Request $request, VendorPortfolioImage $portfolio): JsonResponse
    {
        $vendor = $this->vendor($request);
        $this->assertOwnsPortfolioImage($portfolio, $vendor);

        StoresUploadedFiles::delete($portfolio->image_path);
        $portfolio->delete();

        return $this->success(null, 'Portfolio image removed.');
    }
}
