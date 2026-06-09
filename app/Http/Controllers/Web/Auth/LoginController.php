<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Web\WebController;
use App\Models\Customer;
use App\Services\Auth\OtpService;
use App\Support\CodeGenerator;
use App\Support\StoresUploadedFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends WebController
{
    public function __construct(
        protected OtpService $otp
    ) {}

    public function showLogin(): View
    {
        return view('web.auth.login');
    }

    public function showRegisterMobile(): View
    {
        return view('web.auth.register');
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'string', 'max:20'],
            'type' => ['required', 'in:login,register'],
        ]);

        $payload = $this->otp->send(OtpService::ACTOR_CUSTOMER, $data['mobile'], $data['type']);

        $request->session()->put('web_otp', [
            'mobile' => $payload['mobile'],
            'type' => $data['type'],
            'sent_at' => now()->timestamp,
        ]);

        if (config('app.debug') && isset($payload['otp'])) {
            $request->session()->flash('info', 'Dev OTP: '.$payload['otp']);
        }

        return redirect()->route('web.verify-otp');
    }

    public function showVerifyOtp(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('web_otp')) {
            return redirect()->route('web.login')->with('error', 'Start by entering your mobile number.');
        }

        return view('web.auth.verify-otp', [
            'otpSession' => $request->session()->get('web_otp'),
        ]);
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $otpSession = $request->session()->get('web_otp');

        if (! $otpSession) {
            return redirect()->route('web.login')->with('error', 'Session expired. Request OTP again.');
        }

        $data = $request->validate([
            'otp' => ['required', 'digits:4'],
        ]);

        $payload = $this->otp->verify(
            OtpService::ACTOR_CUSTOMER,
            $otpSession['mobile'],
            $data['otp'],
            $otpSession['type']
        );

        if ($payload['type'] === OtpService::TYPE_LOGIN) {
            $customer = $this->otp->findActor(OtpService::ACTOR_CUSTOMER, $otpSession['mobile']);

            try {
                $this->otp->ensureActorCanAuthenticate(OtpService::ACTOR_CUSTOMER, $customer);
            } catch (\Illuminate\Validation\ValidationException $exception) {
                return redirect()->route('web.login')
                    ->with('error', collect($exception->errors())->flatten()->first());
            }

            Auth::guard('customer')->login($customer, true);
            $request->session()->forget('web_otp');
            $request->session()->regenerate();

            return redirect()->intended(route('web.home'))->with('success', 'Welcome back!');
        }

        if (Auth::guard('customer')->check()) {
            Auth::guard('customer')->logout();
        }

        $request->session()->put('web_register', [
            'registration_token' => $payload['registration_token'],
            'mobile' => $otpSession['mobile'],
        ]);
        $request->session()->forget('web_otp');

        return redirect()->route('web.register.complete');
    }

    public function showRegisterComplete(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('web_register')) {
            return redirect()->route('web.login')->with('error', 'Verify OTP before completing registration.');
        }

        return view('web.auth.complete-register', [
            'registerSession' => $request->session()->get('web_register'),
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $registerSession = $request->session()->get('web_register');

        if (! $registerSession) {
            return redirect()->route('web.login')->with('error', 'Registration session expired.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'registration_token' => ['required', 'string'],
        ]);

        if ($data['registration_token'] !== $registerSession['registration_token']) {
            return redirect()->route('web.login')->with('error', 'Registration session invalid. Please verify OTP again.');
        }

        $mobile = $this->otp->consumeRegistrationToken(
            OtpService::ACTOR_CUSTOMER,
            $data['registration_token']
        );

        if (Customer::query()->where('mobile', $mobile)->exists()) {
            return redirect()->route('web.login')->with('error', 'Account already exists. Please login.');
        }

        $customer = Customer::query()->create([
            'customer_code' => CodeGenerator::customerCode(),
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'mobile' => $mobile,
            'status' => 'active',
            'is_verified' => true,
            'is_guest' => false,
            'registered_at' => now(),
        ]);

        Auth::guard('customer')->login($customer, true);
        $request->session()->forget('web_register');
        $request->session()->regenerate();

        return redirect()->route('web.profile.measurements.create')
            ->with('success', 'Account created! Add your measurements for a better fit.');
    }

    public function guest(Request $request): RedirectResponse
    {
        $customer = Customer::query()->create([
            'customer_code' => CodeGenerator::customerCode(),
            'name' => 'Guest',
            'mobile' => '9'.now()->format('mdHis').random_int(10, 99),
            'status' => 'active',
            'is_verified' => false,
            'is_guest' => true,
            'registered_at' => now(),
        ]);

        Auth::guard('customer')->login($customer, true);
        $request->session()->regenerate();

        return redirect()->route('web.home')->with('success', 'Browsing as guest.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('web.home')->with('success', 'Logged out successfully.');
    }
}
