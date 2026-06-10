<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use App\Models\Admin;
use App\Support\AdminValidationRules;
use App\Models\AdminLoginLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('admin.auth.login');
    }

    public function login(AdminLoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        $loginField = AdminValidationRules::looksLikeEmail($credentials['login']) ? 'email' : 'username';

        $admin = Admin::query()
            ->where($loginField, $credentials['login'])
            ->first();

        if (! $admin || ! $admin->isActive()) {
            AdminLoginLog::query()->create([
                'admin_id' => $admin?->id,
                'email' => $admin?->email ?? ($loginField === 'email' ? $credentials['login'] : null),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'failed',
                'failure_reason' => $admin ? 'Account inactive' : 'Account not found',
                'logged_in_at' => now(),
            ]);

            return back()
                ->withInput($request->only('login', 'remember'))
                ->with('error', $admin ? 'Your account is not active.' : 'Invalid username/email ID or password.');
        }

        if (! Auth::guard('admin')->attempt([
            $loginField => $credentials['login'],
            'password' => $credentials['password'],
        ], $request->boolean('remember'))) {
            AdminLoginLog::query()->create([
                'admin_id' => $admin->id,
                'email' => $loginField === 'email' ? $credentials['login'] : $admin->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'failed',
                'failure_reason' => 'Invalid credentials',
                'logged_in_at' => now(),
            ]);

            return back()
                ->withInput($request->only('login', 'remember'))
                ->with('error', 'Invalid username/email ID or password.');
        }

        $request->session()->regenerate();

        $admin = Auth::guard('admin')->user();
        $admin?->loadMissing(['role.permissions', 'assignedCities']);

        $admin->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'last_login_device' => $request->userAgent(),
        ])->save();

        AdminLoginLog::query()->create([
            'admin_id' => $admin->id,
            'email' => $admin->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'success',
            'logged_in_at' => now(),
        ]);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')
            ->with('success', 'You have been signed out.');
    }
}
