<?php

namespace App\Http\Controllers\Web;

use App\Support\StoresUploadedFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends WebController
{
    public function edit(): View
    {
        return view('web.profile.edit', [
            'customer' => Auth::guard('customer')->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'profile_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ]);

        $customer->fill(collect($data)->only(['name', 'email', 'city'])->all());

        if ($request->hasFile('profile_image')) {
            $customer->profile_image_path = StoresUploadedFiles::replace(
                $request->file('profile_image'),
                $customer->profile_image_path,
                'customers/profile-images'
            );
        }

        $customer->save();

        Auth::guard('customer')->setUser($customer->fresh());

        return back()->with('success', 'Profile updated successfully.');
    }

    public function measurements(): View
    {
        return view('web.profile.measurements.index', [
            'customer' => Auth::guard('customer')->user(),
        ]);
    }

    public function createMeasurement(): View
    {
        return view('web.profile.measurements.form', [
            'customer' => Auth::guard('customer')->user(),
            'profile' => null,
        ]);
    }

    public function addresses(): View
    {
        return view('web.profile.addresses.index', [
            'customer' => Auth::guard('customer')->user(),
        ]);
    }
}
