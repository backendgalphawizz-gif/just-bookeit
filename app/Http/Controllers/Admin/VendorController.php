<?php

namespace App\Http\Controllers\Admin;

use App\Models\Vendor;
use App\Http\Requests\Admin\VendorRequest;
use App\Support\AppliesListDateFilter;
use App\Support\CodeGenerator;
use App\Support\StoresUploadedFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorController extends AdminController
{
    use AppliesListDateFilter;

    protected string $permissionModule = 'vendors';

    public function index(Request $request): View
    {
        $this->validateListDateRange($request);

        $vendors = $this->applyDateRange(Vendor::query(), $request)
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($q) use ($term) {
                    $q->where('brand_name', 'like', $term)
                        ->orWhere('owner_name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('vendor_code', 'like', $term);
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.vendors.index', compact('vendors'));
    }

    public function create(): View
    {
        return view('admin.vendors.create');
    }

    public function store(VendorRequest $request): RedirectResponse
    {
        $data = $request->vendorData();

        $vendor = Vendor::query()->create([
            ...$data,
            'vendor_code' => CodeGenerator::vendorCode(),
            'status' => $data['status'] ?? 'pending',
            'approved_at' => ($data['status'] ?? '') === 'active' ? now() : null,
        ]);

        $this->applyVendorImages($vendor, $request);
        $vendor->save();

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor created successfully.');
    }

    public function show(Vendor $vendor): View
    {
        $vendor->load(['orders' => fn ($q) => $q->latest()->limit(10), 'orders.customer']);

        return view('admin.vendors.show', compact('vendor'));
    }

    public function edit(Vendor $vendor): View
    {
        return view('admin.vendors.edit', compact('vendor'));
    }

    public function update(VendorRequest $request, Vendor $vendor): RedirectResponse
    {
        $data = $request->vendorData();

        if ($data['status'] === 'active' && ! $vendor->approved_at) {
            $data['approved_at'] = now();
        }

        $vendor->fill($data);
        $this->applyVendorImages($vendor, $request);
        $vendor->save();

        return redirect()->route('admin.vendors.show', $vendor)->with('success', 'Vendor updated successfully.');
    }

    public function destroy(Vendor $vendor): RedirectResponse
    {
        if ($vendor->orders()->exists()) {
            return back()->with('error', 'Cannot delete vendor with existing orders.');
        }

        $vendor->delete();

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor deleted successfully.');
    }

    public function approve(Vendor $vendor): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $vendor->update(['status' => 'active', 'approved_at' => now()]);

        return back()->with('success', "Vendor {$vendor->brand_name} approved.");
    }

    public function reject(Vendor $vendor): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $vendor->update(['status' => 'rejected', 'approved_at' => null]);

        return back()->with('success', "Vendor {$vendor->brand_name} rejected.");
    }

    public function suspend(Vendor $vendor): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $vendor->update(['status' => 'suspended']);

        return back()->with('success', "Vendor {$vendor->brand_name} suspended.");
    }

    private function applyVendorImages(Vendor $vendor, VendorRequest $request): void
    {
        if ($request->hasFile('profile_image')) {
            $vendor->profile_image_path = StoresUploadedFiles::replace(
                $request->file('profile_image'),
                $vendor->profile_image_path,
                'vendors/profile-images'
            );
        }

        if ($request->hasFile('shop_logo')) {
            $vendor->shop_logo_path = StoresUploadedFiles::replace(
                $request->file('shop_logo'),
                $vendor->shop_logo_path,
                'vendors/shop-logos'
            );
        }
    }

}
