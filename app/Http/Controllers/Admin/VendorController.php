<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RecordsAccountStatusHistory;
use App\Models\AccountStatusHistory;
use App\Models\Vendor;
use App\Models\VendorShopImage;
use App\Http\Requests\Admin\VendorRequest;
use App\Models\Category;
use App\Support\AdminCityScope;
use App\Support\AdminValidationRules;
use App\Support\AppliesListDateFilter;
use App\Support\CodeGenerator;
use App\Support\StoresUploadedFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorController extends AdminController
{
    use AppliesListDateFilter;
    use RecordsAccountStatusHistory;

    protected string $permissionModule = 'vendors';

    public function index(Request $request): View
    {
        $this->validateListDateRange($request);

        $vendors = AdminCityScope::scopeVendors(
            $this->applyDateRange(Vendor::query(), $request)
        )
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
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.vendors.index', compact('vendors'));
    }

    public function create(): View
    {
        return view('admin.vendors.create', [
            'categories' => Category::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
        ]);
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
        $this->applyShopImages($vendor, $request);

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor created successfully.');
    }

    public function show(Vendor $vendor): View
    {
        $vendor->load([
            'suspendedBy',
            'shopImages',
            'orders' => fn ($q) => $q->latest()->limit(10),
            'orders.customer',
            'statusHistories' => fn ($q) => $q->with('admin')->orderByDesc('created_at'),
        ]);

        return view('admin.vendors.show', compact('vendor'));
    }

    public function edit(Vendor $vendor): View
    {
        $vendor->load('shopImages');

        return view('admin.vendors.edit', [
            'vendor' => $vendor,
            'categories' => Category::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function update(VendorRequest $request, Vendor $vendor): RedirectResponse
    {
        $data = $request->vendorData();

        if ($data['status'] === 'active') {
            if (! $vendor->approved_at) {
                $data['approved_at'] = now();
            }
            $data['rejection_reason'] = null;
            $data['suspension_reason'] = null;
            $data['suspended_at'] = null;
            $data['suspended_by'] = null;
        }

        $previousStatus = $vendor->status;
        $vendor->fill($data);
        $this->applyVendorImages($vendor, $request);
        $vendor->save();

        if ($previousStatus !== $vendor->status) {
            $this->recordAccountStatusHistory(
                $vendor,
                AccountStatusHistory::ACTION_STATUS_UPDATE,
                $previousStatus,
                $vendor->status,
            );
        }
        $this->applyShopImages($vendor, $request);

        return redirect()->route('admin.vendors.show', $vendor)->with('success', 'Vendor updated successfully.');
    }

    public function destroy(Vendor $vendor): RedirectResponse
    {
        if ($vendor->orders()->exists()) {
            return back()->with('error', 'This vendor has orders on record and cannot be deleted.');
        }

        $vendor->delete();

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor deleted successfully.');
    }

    public function approve(Vendor $vendor): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $previousStatus = $vendor->status;

        $vendor->update([
            'status' => 'active',
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        $this->recordAccountStatusHistory(
            $vendor,
            AccountStatusHistory::ACTION_APPROVE,
            $previousStatus,
            'active',
        );

        return back()->with('success', "Vendor {$vendor->brand_name} approved.");
    }

    public function bulkApprove(Request $request): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $data = $request->validate([
            'vendor_ids' => ['required', 'array', 'min:1'],
            'vendor_ids.*' => ['integer', 'exists:vendors,id'],
        ]);

        $pendingVendors = AdminCityScope::scopeVendors(Vendor::query())
            ->whereIn('id', $data['vendor_ids'])
            ->where('status', 'pending')
            ->get(['id', 'status']);

        if ($pendingVendors->isEmpty()) {
            return back()->with('error', 'Select at least one pending vendor to approve.');
        }

        foreach ($pendingVendors as $pendingVendor) {
            $this->recordAccountStatusHistory(
                $pendingVendor,
                AccountStatusHistory::ACTION_BULK_APPROVE,
                'pending',
                'active',
            );
        }

        $approved = Vendor::query()
            ->whereIn('id', $pendingVendors->pluck('id'))
            ->update([
                'status' => 'active',
                'approved_at' => now(),
                'rejection_reason' => null,
            ]);

        if ($approved === 0) {
            return back()->with('error', 'Select at least one pending vendor to approve.');
        }

        return back()->with('success', $approved.' vendor(s) approved successfully.');
    }

    public function reject(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $data = $request->validate(
            AdminValidationRules::accountRejection(),
            AdminValidationRules::messages(),
            AdminValidationRules::attributes()
        );

        $previousStatus = $vendor->status;

        $vendor->update([
            'status' => 'rejected',
            'approved_at' => null,
            'rejection_reason' => $data['rejection_reason'],
        ]);

        $this->recordAccountStatusHistory(
            $vendor,
            AccountStatusHistory::ACTION_REJECT,
            $previousStatus,
            'rejected',
            $data['rejection_reason'],
        );

        return back()->with('success', "Vendor {$vendor->brand_name} rejected.");
    }

    public function suspend(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $data = $request->validate(
            AdminValidationRules::vendorSuspend(),
            AdminValidationRules::messages(),
            AdminValidationRules::attributes()
        );

        $previousStatus = $vendor->status;

        $vendor->update([
            'status' => 'suspended',
            'suspension_reason' => $data['suspension_reason'],
            'suspended_at' => now(),
            'suspended_by' => auth('admin')->id(),
        ]);

        $this->recordAccountStatusHistory(
            $vendor,
            AccountStatusHistory::ACTION_SUSPEND,
            $previousStatus,
            'suspended',
            $data['suspension_reason'],
        );

        return back()->with('success', "Vendor {$vendor->brand_name} suspended.");
    }

    public function activate(Vendor $vendor): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $previousStatus = $vendor->status;

        $vendor->update([
            'status' => 'active',
            'rejection_reason' => null,
            'suspension_reason' => null,
            'suspended_at' => null,
            'suspended_by' => null,
            'approved_at' => $vendor->approved_at ?? now(),
        ]);

        $this->recordAccountStatusHistory(
            $vendor,
            AccountStatusHistory::ACTION_ACTIVATE,
            $previousStatus,
            'active',
        );

        return back()->with('success', "Vendor {$vendor->brand_name} reactivated.");
    }

    private function applyVendorImages(Vendor $vendor, VendorRequest $request): void
    {
        $files = [
            'profile_image' => ['column' => 'profile_image_path', 'dir' => 'vendors/profile-images'],
            'shop_logo' => ['column' => 'shop_logo_path', 'dir' => 'vendors/shop-logos'],
            'aadhar_front' => ['column' => 'aadhar_front_path', 'dir' => 'vendors/aadhar/front'],
            'aadhar_back' => ['column' => 'aadhar_back_path', 'dir' => 'vendors/aadhar/back'],
            'pan_card' => ['column' => 'pan_card_path', 'dir' => 'vendors/pan-cards'],
        ];

        foreach ($files as $input => $config) {
            if (! $request->hasFile($input)) {
                continue;
            }

            $vendor->{$config['column']} = StoresUploadedFiles::replace(
                $request->file($input),
                $vendor->{$config['column']},
                $config['dir']
            );
        }
    }

    private function applyShopImages(Vendor $vendor, VendorRequest $request): void
    {
        if ($request->filled('remove_shop_image_ids')) {
            $ids = collect($request->input('remove_shop_image_ids'))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            VendorShopImage::query()
                ->where('vendor_id', $vendor->id)
                ->whereIn('id', $ids)
                ->get()
                ->each(function (VendorShopImage $image) {
                    StoresUploadedFiles::delete($image->image_path);
                    $image->delete();
                });
        }

        if (! $request->hasFile('shop_images')) {
            return;
        }

        $sortOrder = (int) ($vendor->shopImages()->max('sort_order') ?? 0);

        foreach ($request->file('shop_images') as $file) {
            $sortOrder++;
            VendorShopImage::query()->create([
                'vendor_id' => $vendor->id,
                'image_path' => StoresUploadedFiles::store($file, 'vendors/shop-images'),
                'sort_order' => $sortOrder,
            ]);
        }
    }

}
