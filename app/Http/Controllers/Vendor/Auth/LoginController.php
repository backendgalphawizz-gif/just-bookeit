<?php

namespace App\Http\Controllers\Vendor\Auth;

use App\Http\Controllers\Vendor\VendorController;
use App\Models\Vendor;
use App\Services\Auth\OtpService;
use App\Support\CodeGenerator;
use App\Support\StoresUploadedFiles;
use App\Support\VendorValidationRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends VendorController
{
    public function __construct(
        protected OtpService $otp
    ) {}

    public function showLogin(): View
    {
        return view('vendor.auth.login');
    }

    public function showRegister(Request $request): View
    {
        $errorStep = 1;
        if ($errors = session('errors')) {
            $bag = $errors->getBag('default')->getMessages();
            $step2 = ['shop_name', 'service_types', 'business_mobile', 'business_mail', 'aadhar_number', 'gst_no', 'address', 'city', 'state', 'country', 'pincode', 'shop_logo', 'pan_card'];
            $step3 = ['account_name', 'account_no', 'bank_name', 'ifsc_code', 'account_type'];
            foreach (array_keys($bag) as $field) {
                $base = explode('.', $field)[0];
                if (in_array($base, $step3, true)) {
                    $errorStep = 3;
                    break;
                }
                if (in_array($base, $step2, true)) {
                    $errorStep = 2;
                }
            }
        }

        return view('vendor.auth.register', [
            'serviceOptions' => VendorValidationRules::SERVICE_TYPES,
            'initialStep' => (int) old('_step', $errorStep),
        ]);
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        $data = $this->validateVendor($request, array_merge(
            VendorValidationRules::mobile(),
            ['type' => ['required', 'in:login,register']]
        ));

        if ($data['type'] === OtpService::TYPE_LOGIN) {
            $vendor = $this->otp->findActor(
                OtpService::ACTOR_VENDOR,
                $this->otp->normalizeMobile($data['mobile'])
            );

            if ($vendor) {
                try {
                    $this->otp->ensureActorCanAuthenticate(OtpService::ACTOR_VENDOR, $vendor);
                } catch (\Illuminate\Validation\ValidationException $exception) {
                    return back()->with('error', collect($exception->errors())->flatten()->first());
                }
            }
        }

        if ($data['type'] === OtpService::TYPE_REGISTER) {
            return redirect()->route('vendor.register');
        }

        $payload = $this->otp->send(OtpService::ACTOR_VENDOR, $data['mobile'], $data['type']);

        $this->storeVendorOtpSession($request, $payload['mobile'], $data['type']);

        if (config('app.debug') && isset($payload['otp'])) {
            $request->session()->flash('info', 'Dev OTP: '.$payload['otp']);
        }

        return redirect()
            ->route('vendor.verify-otp')
            ->with('success', 'A new OTP has been sent to your mobile number.');
    }

    public function resendOtp(Request $request): RedirectResponse
    {
        $otpSession = $request->session()->get('vendor_otp');

        if (! $otpSession) {
            return redirect()->route('vendor.login')->with('error', 'Session expired. Enter your mobile number again.');
        }

        $cooldown = $this->otpResendCooldownSeconds();
        $elapsed = now()->timestamp - (int) ($otpSession['sent_at'] ?? 0);

        if ($elapsed < $cooldown) {
            $wait = $cooldown - $elapsed;

            return back()->with('error', 'Please wait '.$wait.' seconds before requesting a new code.');
        }

        try {
            $payload = $this->otp->send(
                OtpService::ACTOR_VENDOR,
                $otpSession['mobile'],
                $otpSession['type']
            );
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        $this->storeVendorOtpSession($request, $payload['mobile'], $otpSession['type']);

        if (config('app.debug') && isset($payload['otp'])) {
            $request->session()->flash('info', 'Dev OTP: '.$payload['otp']);
        }

        return redirect()
            ->route('vendor.verify-otp')
            ->with('success', 'A new OTP has been sent to your mobile number.');
    }

    public function showVerifyOtp(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('vendor_otp')) {
            return redirect()->route('vendor.login')->with('error', 'Enter your mobile number first.');
        }

        $otpSession = $request->session()->get('vendor_otp');
        $cooldown = $this->otpResendCooldownSeconds();
        $elapsed = now()->timestamp - (int) ($otpSession['sent_at'] ?? 0);

        return view('vendor.auth.verify-otp', [
            'otpSession' => $otpSession,
            'maskedMobile' => '+91 ******'.substr($otpSession['mobile'], -3),
            'resendIn' => max(0, $cooldown - $elapsed),
            'resendCooldown' => $cooldown,
        ]);
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $otpSession = $request->session()->get('vendor_otp');

        if (! $otpSession) {
            return redirect()->route('vendor.login')->with('error', 'Session expired. Request OTP again.');
        }

        $data = $this->validateVendor($request, [
            'otp' => ['required', 'digits:4'],
        ]);

        $payload = $this->otp->verify(
            OtpService::ACTOR_VENDOR,
            $otpSession['mobile'],
            $data['otp'],
            $otpSession['type']
        );

        if ($payload['type'] === OtpService::TYPE_LOGIN) {
            $vendor = $this->otp->findActor(OtpService::ACTOR_VENDOR, $otpSession['mobile']);

            Auth::guard('vendor')->login($vendor);
            $request->session()->forget('vendor_otp');
            $request->session()->regenerate();

            return redirect()->intended(route('vendor.dashboard'))->with('success', 'Welcome back!');
        }

        $pending = $request->session()->get('vendor_register_pending');

        if (! $pending || ($pending['mobile'] ?? null) !== $otpSession['mobile']) {
            $request->session()->forget('vendor_otp');

            return redirect()->route('vendor.register')
                ->with('error', 'Registration session expired. Please fill the form again.');
        }

        if (Vendor::query()->where('mobile', $otpSession['mobile'])->exists()) {
            $request->session()->forget(['vendor_otp', 'vendor_register_pending']);

            return redirect()->route('vendor.login')->with('error', 'Account already exists. Please login.');
        }

        // Confirm OTP / registration token is valid, then create the vendor.
        $this->otp->consumeRegistrationToken(
            OtpService::ACTOR_VENDOR,
            $payload['registration_token']
        );

        Vendor::query()->create([
            'vendor_code' => CodeGenerator::vendorCode(),
            'shop_name' => $pending['shop_name'],
            'brand_name' => $pending['shop_name'],
            'owner_name' => $pending['owner_name'],
            'email' => $pending['email'],
            'mobile' => $otpSession['mobile'],
            'business_mobile' => $pending['business_mobile'] ?? null,
            'business_email' => $pending['business_email'] ?? null,
            'aadhar_number' => $pending['aadhar_number'] ?? null,
            'gst_number' => $pending['gst_number'] ?? null,
            'address' => $pending['address'] ?? null,
            'city' => $pending['city'] ?? null,
            'state' => $pending['state'] ?? null,
            'country' => $pending['country'] ?? null,
            'pincode' => $pending['pincode'] ?? null,
            'service_types' => $pending['service_types'] ?? null,
            'account_name' => $pending['account_name'] ?? null,
            'account_number' => $pending['account_number'] ?? null,
            'bank_name' => $pending['bank_name'] ?? null,
            'ifsc_code' => $pending['ifsc_code'] ?? null,
            'account_type' => $pending['account_type'] ?? null,
            'status' => 'pending',
            'aadhar_front_path' => $pending['aadhar_front_path'] ?? null,
            'aadhar_back_path' => $pending['aadhar_back_path'] ?? null,
            'cover_image_path' => $pending['cover_image_path'] ?? null,
            'profile_image_path' => $pending['profile_image_path'] ?? null,
            'shop_logo_path' => $pending['shop_logo_path'] ?? null,
            'pan_card_path' => $pending['pan_card_path'] ?? null,
        ]);

        $request->session()->forget(['vendor_otp', 'vendor_register_pending']);

        return redirect()->route('vendor.register.success');
    }

    public function showRegisterComplete(): RedirectResponse
    {
        return redirect()->route('vendor.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $this->validateVendor($request, VendorValidationRules::register());

        $mobile = $this->otp->normalizeMobile($data['mobile']);

        if (Vendor::query()->where('mobile', $mobile)->exists()) {
            return redirect()->route('vendor.login')->with('error', 'Account already exists. Please login.');
        }

        $vendor = Vendor::query()->create([
            'vendor_code' => CodeGenerator::vendorCode(),
            'shop_name' => $data['shop_name'],
            'brand_name' => $data['shop_name'],
            'owner_name' => $data['owner_name'],
            'email' => $data['email'],
            'mobile' => $mobile,
            'business_mobile' => $data['business_mobile'] ?? null,
            'business_email' => $data['business_mail'] ?? null,
            'aadhar_number' => $data['aadhar_number'] ?? null,
            'gst_number' => isset($data['gst_no']) ? strtoupper($data['gst_no']) : null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'country' => $data['country'] ?? null,
            'pincode' => $data['pincode'] ?? null,
            'service_types' => implode(', ', VendorValidationRules::normalizeServiceTypes($data['service_types'] ?? [])),
            'account_name' => $data['account_name'],
            'account_number' => $data['account_no'],
            'bank_name' => $data['bank_name'],
            'ifsc_code' => strtoupper($data['ifsc_code']),
            'account_type' => $data['account_type'],
            'status' => 'active',
            'aadhar_front_path' => StoresUploadedFiles::store($request->file('aadhar_front'), 'vendors/aadhar/front'),
            'aadhar_back_path' => StoresUploadedFiles::store($request->file('aadhar_back'), 'vendors/aadhar/back'),
            'cover_image_path' => $request->file('cover_image')
                ? StoresUploadedFiles::store($request->file('cover_image'), 'vendors/cover-images')
                : null,
            'profile_image_path' => $request->file('profile_image')
                ? StoresUploadedFiles::store($request->file('profile_image'), 'vendors/profile-images')
                : null,
            'shop_logo_path' => $request->file('shop_logo')
                ? StoresUploadedFiles::store($request->file('shop_logo'), 'vendors/shop-logos')
                : null,
            'pan_card_path' => $request->file('pan_card')
                ? StoresUploadedFiles::store($request->file('pan_card'), 'vendors/pan-cards')
                : null,
        ]);

        Auth::guard('vendor')->login($vendor);
        $request->session()->regenerate();

        return redirect()->route('vendor.register.success');
    }

    public function showRegisterSuccess(): View
    {
        return view('vendor.auth.register-success');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('vendor')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('vendor.login')->with('success', 'Logged out successfully.');
    }

    protected function storeVendorOtpSession(Request $request, string $mobile, string $type): void
    {
        $request->session()->put('vendor_otp', [
            'mobile' => $mobile,
            'type' => $type,
            'sent_at' => now()->timestamp,
        ]);
    }

    protected function otpResendCooldownSeconds(): int
    {
        return max(1, (int) config('api.otp_resend_cooldown_seconds', 48));
    }
}
