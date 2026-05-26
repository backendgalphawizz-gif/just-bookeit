<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminProfileController extends Controller
{
    public function edit(): View
    {
        return view('admin.profile.edit', [
            'admin' => auth('admin')->user()->load('role'),
        ]);
    }

    public function update(AdminProfileRequest $request): RedirectResponse
    {
        $admin = auth('admin')->user();
        $data = $request->validated();
        unset($data['avatar']);

        if ($request->hasFile('avatar')) {
            if ($admin->avatar_path && Storage::disk('public')->exists($admin->avatar_path)) {
                Storage::disk('public')->delete($admin->avatar_path);
            }
            $data['avatar_path'] = $request->file('avatar')->store('admins/avatars', 'public');
        }

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $admin->update($data);

        return redirect()->route('admin.profile.edit')->with('success', 'Profile updated successfully.');
    }
}
