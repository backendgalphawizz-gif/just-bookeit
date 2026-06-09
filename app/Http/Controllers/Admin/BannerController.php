<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\BannerRequest;
use App\Models\Banner;
use App\Support\AppliesListDateFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BannerController extends AdminController
{
    use AppliesListDateFilter;

    protected string $permissionModule = 'banners';

    public function index(Request $request): View
    {
        $this->validateListDateRange($request);
        $audience = $this->resolveAudience($request->string('audience', Banner::AUDIENCE_CUSTOMER)->toString());

        $banners = $this->applyDateRange(Banner::query()->forAudience($audience), $request)
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('active'), fn ($q) => $q->where('is_active', $request->boolean('active')))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.banners.index', compact('banners', 'audience'));
    }

    public function create(Request $request): View
    {
        $audience = $this->resolveAudience($request->string('audience', Banner::AUDIENCE_CUSTOMER)->toString());

        return view('admin.banners.create', compact('audience'));
    }

    public function store(BannerRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['image']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('banners', 'public');
        }

        Banner::query()->create($data);

        return redirect()
            ->route('admin.banners.index', ['audience' => $data['audience']])
            ->with('success', 'Banner created successfully.');
    }

    public function edit(Banner $banner): View
    {
        return view('admin.banners.edit', compact('banner'));
    }

    public function preview(Banner $banner): View
    {
        return view('admin.banners.preview', compact('banner'));
    }

    public function update(BannerRequest $request, Banner $banner): RedirectResponse
    {
        $data = $request->validated();
        unset($data['image']);

        if ($request->hasFile('image')) {
            $this->deleteImage($banner);
            $data['image_path'] = $request->file('image')->store('banners', 'public');
        }

        $banner->update($data);

        return redirect()
            ->route('admin.banners.index', ['audience' => $banner->audience])
            ->with('success', 'Banner updated successfully.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $audience = $banner->audience;
        $this->deleteImage($banner);
        $banner->delete();

        return redirect()
            ->route('admin.banners.index', ['audience' => $audience])
            ->with('success', 'Banner deleted successfully.');
    }

    protected function deleteImage(Banner $banner): void
    {
        if ($banner->image_path && Storage::disk('public')->exists($banner->image_path)) {
            Storage::disk('public')->delete($banner->image_path);
        }
    }

    protected function resolveAudience(string $audience): string
    {
        return in_array($audience, Banner::AUDIENCES, true) ? $audience : Banner::AUDIENCE_CUSTOMER;
    }
}
