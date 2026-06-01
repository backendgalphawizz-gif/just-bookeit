<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\AdminUserRequest;
use App\Models\Admin;
use App\Models\Role;
use App\Support\AdminCityScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminUserController extends AdminController
{
    protected string $permissionModule = 'admins';

    public function index(): View
    {
        $admins = Admin::query()
            ->with(['role', 'assignedCities'])
            ->orderBy('name')
            ->paginate(15);

        return view('admin.admins.index', compact('admins'));
    }

    public function create(): View
    {
        return view('admin.admins.create', [
            'roles' => Role::query()->where('is_active', true)->orderBy('name')->get(),
            'cities' => AdminCityScope::availableCities(),
        ]);
    }

    public function store(AdminUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        unset($data['city']);

        $admin = Admin::query()->create($data);
        $admin->syncAssignedCity($request->cityName());

        return redirect()->route('admin.admins.index')->with('success', 'Admin user created successfully.');
    }

    public function edit(Admin $admin): View
    {
        return view('admin.admins.edit', [
            'admin' => $admin->load('assignedCities'),
            'roles' => Role::query()->where('is_active', true)->orderBy('name')->get(),
            'cities' => AdminCityScope::availableCities(),
        ]);
    }

    public function update(AdminUserRequest $request, Admin $admin): RedirectResponse
    {
        $data = $request->validated();
        unset($data['city']);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $admin->update($data);
        $admin->syncAssignedCity($request->cityName());
        $admin->load(['role.permissions', 'assignedCities']);

        return redirect()->route('admin.admins.index')->with(
            'success',
            'Admin user updated successfully. Assigned role: '.($admin->role?->name ?? '—').'.'
        );
    }

    public function destroy(Admin $admin): RedirectResponse
    {
        if ($admin->id === auth('admin')->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($admin->role?->slug === 'super_admin' && Admin::query()->whereHas('role', fn ($q) => $q->where('slug', 'super_admin'))->where('status', 'active')->count() <= 1) {
            return back()->with('error', 'At least one active Super Admin is required.');
        }

        $admin->delete();

        return redirect()->route('admin.admins.index')->with('success', 'Admin user deleted successfully.');
    }
}
