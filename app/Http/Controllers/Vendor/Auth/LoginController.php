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

    public function showRegister(): View
    {
        return view('vendor.auth.register');
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        $data = $this->validateVendor($request, array_merge(
            VendorValidationRules::mobile(),
            ['type' => ['required', 'in:login,register']]
        ));

        $payload = $this->otp->send(OtpService::ACTOR_VENDOR, $data['mobile'], $data['type']);

        $request->session()->put('vendor_otp', [
            'mobile' => $payload['mobile'],
            'type' => $data['type'],
            'sent_at' => now()->timestamp,
        ]);

        if (config('app.debug') && isset($payload['otp'])) {
            $request->session()->flash('info', 'Dev OTP: '.$payload['otp']);
        }

        return redirect()->route('vendor.verify-otp');
    }

    public function showVerifyOtp(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('vendor_otp')) {
            return redirect()->route('vendor.login')->with('error', 'Enter your mobile number first.');
        }

        return view('vendor.auth.verify-otp', [
            'otpSession' => $request->session()->get('vendor_otp'),
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

        $request->session()->put('vendor_register', [
            'registration_token' => $payload['registration_token'],
            'mobile' => $otpSession['mobile'],
        ]);
        $request->session()->forget('vendor_otp');

        return redirect()->route('vendor.register.complete');
    }

    public function showRegisterComplete(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('vendor_register')) {
            return redirect()->route('vendor.login')->with('error', 'Verify OTP before completing registration.');
        }

        return view('vendor.auth.complete-register', [
            'registerSession' => $request->session()->get('vendor_register'),
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $registerSession = $request->session()->get('vendor_register');

        if (! $registerSession) {
            return redirect()->route('vendor.login')->with('error', 'Registration session expired.');
        }

        $data = $this->validateVendor($request, array_merge(
            VendorValidationRules::register(),
            ['registration_token' => ['required', 'string']]
        ));

        if ($data['registration_token'] !== $registerSession['registration_token']) {
            return redirect()->route('vendor.login')->with('error', 'Invalid registration session.');
        }

        $mobile = $this->otp->consumeRegistrationToken(
            OtpService::ACTOR_VENDOR,
            $data['registration_token']
        );

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
            'city' => $data['city'] ?? null,
            'service_types' => $data['service_types'],
            'status' => 'pending',
            'aadhar_front_path' => StoresUploadedFiles::store($request->file('aadhar_front'), 'vendors/aadhar/front'),
            'aadhar_back_path' => StoresUploadedFiles::store($request->file('aadhar_back'), 'vendors/aadhar/back'),
        ]);

        Auth::guard('vendor')->login($vendor);
        $request->session()->forget('vendor_register');
        $request->session()->regenerate();

        return redirect()->route('vendor.dashboard')
            ->with('success', 'Registration submitted. Your account is pending admin approval.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('vendor')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('vendor.login')->with('success', 'Logged out successfully.');
    }
}
