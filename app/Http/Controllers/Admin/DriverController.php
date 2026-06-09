<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\DriverRequest;
use App\Models\Driver;
use App\Support\AdminCityScope;
use App\Support\AdminValidationRules;
use App\Support\AppliesListDateFilter;
use App\Support\CodeGenerator;
use App\Support\StoresUploadedFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DriverController extends AdminController
{
    use AppliesListDateFilter;

    protected string $permissionModule = 'drivers';

    public function index(Request $request): View
    {
        $this->validateListDateRange($request);

        $drivers = AdminCityScope::scopeDrivers(
            $this->applyDateRange(Driver::query(), $request)
        )
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('mobile', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('driver_code', 'like', $term);
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.drivers.index', compact('drivers'));
    }

    public function create(): View
    {
        return view('admin.drivers.create');
    }

    public function store(DriverRequest $request): RedirectResponse
    {
        $data = $this->driverData($request);

        $driver = Driver::query()->create([
            ...$data,
            'driver_code' => CodeGenerator::driverCode(),
            'status' => $data['status'] ?? 'pending',
            'approved_at' => ($data['status'] ?? '') === 'active' ? now() : null,
            'registered_at' => now(),
        ]);

        $this->applyDriverImages($driver, $request);
        $driver->save();

        return redirect()->route('admin.drivers.index')->with('success', 'Driver created successfully.');
    }

    public function show(Driver $driver): View
    {
        $driver->load(['orders' => fn ($q) => $q->latest()->limit(10)]);

        return view('admin.drivers.show', compact('driver'));
    }

    public function edit(Driver $driver): View
    {
        return view('admin.drivers.edit', compact('driver'));
    }

    public function update(DriverRequest $request, Driver $driver): RedirectResponse
    {
        $data = $this->driverData($request);

        if (($data['status'] ?? '') === 'rejected' && $driver->status !== 'rejected') {
            return back()
                ->with('error', 'Use the Reject button on the profile page so a reason is recorded for the driver.')
                ->withInput();
        }

        if (($data['status'] ?? $driver->status) === 'active' && ! $driver->approved_at) {
            $data['approved_at'] = now();
            $data['is_verified'] = true;
            $data['rejection_reason'] = null;
        }

        $driver->fill($data);
        $this->applyDriverImages($driver, $request);
        $driver->save();

        return redirect()->route('admin.drivers.show', $driver)->with('success', 'Driver updated successfully.');
    }

    public function destroy(Driver $driver): RedirectResponse
    {
        if ($driver->orders()->exists()) {
            return back()->with('error', 'Cannot delete driver with assigned orders.');
        }

        $driver->delete();

        return redirect()->route('admin.drivers.index')->with('success', 'Driver deleted successfully.');
    }

    public function approve(Driver $driver): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $driver->update([
            'status' => 'active',
            'approved_at' => now(),
            'is_verified' => true,
            'rejection_reason' => null,
        ]);

        return back()->with('success', "Driver {$driver->name} approved.");
    }

    public function reject(Request $request, Driver $driver): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $data = $request->validate(
            AdminValidationRules::accountRejection(),
            AdminValidationRules::messages(),
            AdminValidationRules::attributes()
        );

        $driver->update([
            'status' => 'rejected',
            'approved_at' => null,
            'is_verified' => false,
            'rejection_reason' => $data['rejection_reason'],
        ]);

        return back()->with('success', "Driver {$driver->name} rejected.");
    }

    public function suspend(Driver $driver): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $driver->update(['status' => 'suspended']);

        return back()->with('success', "Driver {$driver->name} suspended.");
    }

    private function driverData(DriverRequest $request): array
    {
        return collect($request->validated())->except([
            'profile_image',
            'aadhar',
            'aadhar_front',
            'aadhar_back',
            'driving_licence',
        ])->all();
    }

    private function applyDriverImages(Driver $driver, DriverRequest $request): void
    {
        $files = [
            'profile_image' => ['column' => 'profile_image_path', 'dir' => 'drivers/profile-images'],
            'aadhar' => ['column' => 'aadhar_path', 'dir' => 'drivers/aadhar'],
            'aadhar_front' => ['column' => 'aadhar_front_path', 'dir' => 'drivers/aadhar/front'],
            'aadhar_back' => ['column' => 'aadhar_back_path', 'dir' => 'drivers/aadhar/back'],
            'driving_licence' => ['column' => 'driving_licence_path', 'dir' => 'drivers/driving-licence'],
        ];

        foreach ($files as $input => $config) {
            if (! $request->hasFile($input)) {
                continue;
            }

            $driver->{$config['column']} = StoresUploadedFiles::replace(
                $request->file($input),
                $driver->{$config['column']},
                $config['dir']
            );
        }
    }
}
