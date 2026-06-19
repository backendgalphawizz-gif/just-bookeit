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

        $this->storeVendorOtpSession($request, $payload['mobile'], $data['type']);

        if (config('app.debug') && isset($payload['otp'])) {
            $request->session()->flash('info', 'Dev OTP: '.$payload['otp']);
        }

        return redirect()->route('vendor.verify-otp');
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

            try {
                $this->otp->ensureActorCanAuthenticate(OtpService::ACTOR_VENDOR, $vendor);
            } catch (\Illuminate\Validation\ValidationException $exception) {
                return redirect()->route('vendor.login')
                    ->with('error', collect($exception->errors())->flatten()->first());
            }

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

        $request->session()->forget('vendor_register');

        return redirect()->route('vendor.login')
            ->with('success', 'Registration submitted. You can login once admin approves your account.');
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
