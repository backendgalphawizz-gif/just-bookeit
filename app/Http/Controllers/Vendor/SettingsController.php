<?php

namespace App\Http\Controllers\Vendor;

use App\Models\Faq;
use App\Models\PlatformSetting;
use App\Models\VendorPortfolioImage;
use App\Services\PlatformConfigService;
use App\Support\StoresUploadedFiles;
use App\Support\VendorValidationRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends VendorController
{
    public function __construct(
        protected PlatformConfigService $config
    ) {}

    public function index(Request $request): View
    {
        $vendor = $this->vendor();
        $tab = $request->string('tab', 'profile')->toString();
        $validTabs = collect($this->settingsTabs())->pluck('key')->all();
        if (! in_array($tab, $validTabs, true)) {
            $tab = 'profile';
        }
        $legal = $this->config->legalFor(Faq::AUDIENCE_VENDOR);
        $portfolioAudience = $request->string('audience', 'women')->toString();
        if (! in_array($portfolioAudience, ['women', 'men', 'kids'], true)) {
            $portfolioAudience = 'women';
        }

        $portfolioImages = VendorPortfolioImage::query()
            ->where('vendor_id', $vendor->id)
            ->where('audience', $portfolioAudience)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        return view('vendor.settings.index', [
            'vendor' => $vendor,
            'tab' => $tab,
            'legal' => $legal,
            'serviceOptions' => VendorValidationRules::SERVICE_TYPES,
            'portfolioImages' => $portfolioImages,
            'portfolioAudience' => $portfolioAudience,
            'settingsTabs' => $this->settingsTabs(),
            'legalUpdatedAt' => PlatformSetting::get('legal_updated_at', now()->format('F j, Y')),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $vendor = $this->vendor();
        $tab = $request->string('tab', 'profile')->toString();

        return match ($tab) {
            'bio' => $this->updateBio($request, $vendor),
            'portfolio' => $this->updatePortfolio($request, $vendor),
            'business' => $this->updateBusiness($request, $vendor),
            'bank' => $this->updateBank($request, $vendor),
            default => $this->updateProfile($request, $vendor),
        };
    }

    public function destroyPortfolio(VendorPortfolioImage $portfolioImage): RedirectResponse
    {
        abort_unless($portfolioImage->vendor_id === $this->vendor()->id, 403);

        $audience = $portfolioImage->audience;
        StoresUploadedFiles::delete($portfolioImage->image_path);
        $portfolioImage->delete();

        return redirect()
            ->route('vendor.settings.index', ['tab' => 'portfolio', 'audience' => $audience])
            ->with('success', 'Portfolio image removed.');
    }

    public function toggleActive(Request $request): RedirectResponse
    {
        $vendor = $this->vendor();
        $vendor->is_listing_active = $request->boolean('is_listing_active');
        $vendor->save();

        return back()->with('success', 'Active status updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $this->validateVendor($request, [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $vendor = $this->vendor();
        $vendor->password = $data['password'];
        $vendor->save();

        return redirect()->route('vendor.settings.index', ['tab' => 'password'])
            ->with('success', 'Password updated successfully.');
    }

    protected function updateProfile(Request $request, $vendor): RedirectResponse
    {
        $request->merge([
            'mobile' => $request->filled('mobile') ? $request->input('mobile') : null,
        ]);

        $uploadOnly = $request->string('upload_only')->toString();

        if (in_array($uploadOnly, ['profile_image', 'cover_image'], true)) {
            $request->validate([
                $uploadOnly => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.VendorValidationRules::MAX_IMAGE_KB],
            ], VendorValidationRules::messages(), VendorValidationRules::attributes());
        } else {
            $data = $this->validateVendor($request, VendorValidationRules::profile($vendor->id));
            $vendor->owner_name = $data['owner_name'];
            $vendor->email = $data['email'];
            $vendor->mobile = $data['mobile'] ?? $vendor->mobile;
        }

        if ($request->hasFile('profile_image')) {
            $vendor->profile_image_path = StoresUploadedFiles::replace(
                $request->file('profile_image'),
                $vendor->profile_image_path,
                'vendors/profile-images'
            );
        }

        if ($request->hasFile('cover_image')) {
            $vendor->cover_image_path = StoresUploadedFiles::replace(
                $request->file('cover_image'),
                $vendor->cover_image_path,
                'vendors/cover-images'
            );
        }

        $vendor->save();
        $this->refreshVendorSession($vendor);

        $message = $request->hasFile('profile_image')
            ? 'Profile photo updated.'
            : ($request->hasFile('cover_image') ? 'Cover image updated.' : 'Profile saved successfully.');

        return redirect()->route('vendor.settings.index', ['tab' => 'profile'])
            ->with('success', $message);
    }

    protected function updateBio(Request $request, $vendor): RedirectResponse
    {
        $data = $this->validateVendor($request, [
            'bio' => ['nullable', 'string', 'max:20000', 'regex:'.\App\Support\AdminValidationRules::REGEX_TEXT],
        ]);

        $vendor->bio = $data['bio'] ?? null;
        $vendor->save();

        return redirect()->route('vendor.settings.index', ['tab' => 'bio'])
            ->with('success', 'Bio updated successfully.');
    }

    protected function updatePortfolio(Request $request, $vendor): RedirectResponse
    {
        $data = $this->validateVendor($request, VendorValidationRules::portfolioUpload());

        $imagePath = StoresUploadedFiles::store($request->file('portfolio_image'), 'vendors/portfolio');

        VendorPortfolioImage::query()->create([
            'vendor_id' => $vendor->id,
            'audience' => $data['audience'],
            'image_path' => $imagePath,
            'sort_order' => (int) VendorPortfolioImage::query()
                ->where('vendor_id', $vendor->id)
                ->where('audience', $data['audience'])
                ->max('sort_order') + 1,
        ]);

        return redirect()->route('vendor.settings.index', ['tab' => 'portfolio', 'audience' => $data['audience']])
            ->with('success', 'Portfolio image added.');
    }

    protected function updateBusiness(Request $request, $vendor): RedirectResponse
    {
        $data = $this->validateVendor($request, VendorValidationRules::business());

        $vendor->shop_name = $data['shop_name'];
        $vendor->brand_name = $data['shop_name'];
        $vendor->service_types = implode(', ', $data['service_types'] ?? []);
        $vendor->business_mobile = $data['business_mobile'] ?? null;
        $vendor->business_email = $data['business_mail'] ?? null;
        $vendor->gst_number = isset($data['gst_no']) ? strtoupper($data['gst_no']) : null;
        $vendor->address = $data['address'] ?? null;
        $vendor->save();

        return redirect()->route('vendor.settings.index', ['tab' => 'business'])
            ->with('success', 'Business details saved.');
    }

    protected function updateBank(Request $request, $vendor): RedirectResponse
    {
        $data = $this->validateVendor($request, VendorValidationRules::bank());

        $vendor->account_name = $data['account_name'] ?? null;
        $vendor->account_number = $data['account_no'] ?? null;
        $vendor->bank_name = $data['bank_name'] ?? null;
        $vendor->ifsc_code = isset($data['ifsc_code']) ? strtoupper($data['ifsc_code']) : null;
        $vendor->account_type = $data['account_type'] ?? null;
        $vendor->save();

        return redirect()->route('vendor.settings.index', ['tab' => 'bank'])
            ->with('success', 'Bank details saved.');
    }

    /** @return array<int, array{key: string, label: string, icon: string}> */
    protected function settingsTabs(): array
    {
        return [
            ['key' => 'profile', 'label' => 'Personal Profile', 'icon' => 'user'],
            ['key' => 'bio', 'label' => 'Bio / Description', 'icon' => 'bio'],
            ['key' => 'portfolio', 'label' => 'Portfolio', 'icon' => 'portfolio'],
            ['key' => 'business', 'label' => 'Business Info', 'icon' => 'business'],
            ['key' => 'bank', 'label' => 'Bank Info', 'icon' => 'bank'],
            ['key' => 'password', 'label' => 'Password', 'icon' => 'password'],
            ['key' => 'privacy', 'label' => 'Privacy Policy', 'icon' => 'privacy'],
            ['key' => 'terms', 'label' => 'Terms & Conditions', 'icon' => 'terms'],
            ['key' => 'faq', 'label' => "FAQ's", 'icon' => 'faq'],
        ];
    }
}
