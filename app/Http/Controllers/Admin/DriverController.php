<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\DriverRequest;
use App\Models\Driver;
use App\Support\AppliesListDateFilter;
use App\Support\CodeGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DriverController extends AdminController
{
    use AppliesListDateFilter;

    protected string $permissionModule = 'drivers';

    public function index(Request $request): View
    {
        $this->validateListDateRange($request);

        $drivers = $this->applyDateRange(Driver::query(), $request)
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
        $data = $request->validated();
        unset($data['aadhar']);

        if ($request->hasFile('aadhar')) {
            $data['aadhar_path'] = $request->file('aadhar')->store('drivers/aadhar', 'public');
        }

        Driver::query()->create([
            ...$data,
            'driver_code' => CodeGenerator::driverCode(),
            'status' => $data['status'] ?? 'pending',
            'approved_at' => ($data['status'] ?? '') === 'active' ? now() : null,
            'registered_at' => now(),
        ]);

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
        $data = $request->validated();
        unset($data['aadhar']);

        if ($request->hasFile('aadhar')) {
            if ($driver->aadhar_path && Storage::disk('public')->exists($driver->aadhar_path)) {
                Storage::disk('public')->delete($driver->aadhar_path);
            }
            $data['aadhar_path'] = $request->file('aadhar')->store('drivers/aadhar', 'public');
        }

        if (($data['status'] ?? $driver->status) === 'active' && ! $driver->approved_at) {
            $data['approved_at'] = now();
            $data['is_verified'] = true;
        }

        $driver->update($data);

        return redirect()->route('admin.drivers.show', $driver)->with('success', 'Driver updated successfully.');
    }

    public function destroy(Driver $driver): RedirectResponse
    {
        if ($driver->orders()->exists()) {
            return back()->with('error', 'Cannot delete driver with assigned orders.');
        }

        if ($driver->aadhar_path && Storage::disk('public')->exists($driver->aadhar_path)) {
            Storage::disk('public')->delete($driver->aadhar_path);
        }

        $driver->delete();

        return redirect()->route('admin.drivers.index')->with('success', 'Driver deleted successfully.');
    }

    public function approve(Driver $driver): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $driver->update(['status' => 'active', 'approved_at' => now(), 'is_verified' => true]);

        return back()->with('success', "Driver {$driver->name} approved.");
    }

    public function reject(Driver $driver): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $driver->update(['status' => 'rejected', 'approved_at' => null, 'is_verified' => false]);

        return back()->with('success', "Driver {$driver->name} rejected.");
    }

    public function suspend(Driver $driver): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $driver->update(['status' => 'suspended']);

        return back()->with('success', "Driver {$driver->name} suspended.");
    }
}
