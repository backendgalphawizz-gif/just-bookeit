<?php

namespace App\Http\Controllers\Web;

use App\Models\CustomerAddress;
use App\Models\CustomerMeasurement;
use App\Support\AdminValidationRules;
use App\Support\StoresUploadedFiles;
use App\Support\WebMeasurementForm;
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
            'email' => AdminValidationRules::emailRules(false),
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
        $customer = Auth::guard('customer')->user();

        return view('web.profile.measurements.index', [
            'customer' => $customer,
            'profiles' => $customer->measurements()->latest('id')->get(),
        ]);
    }

    public function createMeasurement(Request $request): View
    {
        $customer = Auth::guard('customer')->user();
        $profile = $customer->measurements()->latest('id')->first();

        return view('web.profile.measurements.form', [
            'customer' => $customer,
            'profile' => $profile,
            'sections' => WebMeasurementForm::sections(),
            'values' => WebMeasurementForm::valuesFromProfile($profile),
            'redirectTo' => $this->safeRedirectTarget($request->query('redirect')),
        ]);
    }

    public function storeMeasurement(Request $request): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'measurement_type' => ['nullable', 'in:women,men,kid'],
            ...collect(WebMeasurementForm::labelToField())
                ->mapWithKeys(fn (string $field) => [$field => ['nullable', 'string', 'max:50']])
                ->all(),
        ]);

        $payload = CustomerMeasurement::normalizeApiPayload(
            WebMeasurementForm::toApiPayload(
                $data,
                $data['name'] ?? 'Default profile',
                $data['measurement_type'] ?? 'women'
            )
        );

        $profile = $customer->measurements()->latest('id')->first();

        if ($profile) {
            $profile->update($payload);
        } else {
            $customer->measurements()->create($payload);
        }

        $redirectTo = $this->safeRedirectTarget($request->input('redirect'));

        return redirect()
            ->to($redirectTo ?? route('web.profile.measurements'))
            ->with('success', 'Measurements saved successfully.');
    }

    /**
     * Only allow redirecting back to a same-host URL to avoid open-redirects.
     */
    private function safeRedirectTarget(?string $url): ?string
    {
        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        $url = trim($url);

        // Relative path within the app (e.g. "/bookings/12/overview").
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return url($url);
        }

        $host = parse_url($url, PHP_URL_HOST);

        if ($host !== null && $host === request()->getHost()) {
            return $url;
        }

        return null;
    }

    public function addresses(): View
    {
        $customer = Auth::guard('customer')->user();

        return view('web.profile.addresses.index', [
            'customer' => $customer,
            'addresses' => $customer->addresses()->orderByDesc('is_default')->orderByDesc('id')->get(),
        ]);
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        $data = $request->validate([
            'label' => ['required', 'string', 'max:50'],
            'name' => ['nullable', 'string', 'max:255'],
            'mobile_number' => ['nullable', 'string', 'regex:'.AdminValidationRules::REGEX_PHONE],
            'house_no' => ['required', 'string', 'max:50'],
            'road_area' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['required', 'string', 'max:10'],
            'country' => ['nullable', 'string', 'max:100'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if ($customer->addresses()->count() === 0) {
            $data['is_default'] = true;
        }

        $address = $customer->addresses()->create([
            'label' => strtoupper($data['label']),
            'name' => $data['name'] ?? $customer->name,
            'mobile_number' => $data['mobile_number'] ?? $customer->mobile,
            'house_no' => $data['house_no'],
            'road_area' => $data['road_area'],
            'address_line' => trim($data['house_no'].', '.$data['road_area']),
            'city' => $data['city'],
            'state' => $data['state'] ?? null,
            'pincode' => $data['pincode'],
            'country' => $data['country'] ?? 'India',
            'is_default' => (bool) ($data['is_default'] ?? false),
        ]);

        if ($address->is_default) {
            $customer->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
        }

        return back()->with('success', 'Address saved.');
    }

    public function destroyAddress(CustomerAddress $address): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($address->customer_id === $customer->id, 403);

        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $customer->addresses()->latest('id')->first()?->update(['is_default' => true]);
        }

        return back()->with('success', 'Address removed.');
    }
}
