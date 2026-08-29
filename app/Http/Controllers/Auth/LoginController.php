<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return Inertia::render('Auth/Login', [
            'labels' => [
                'title' => __('auth.login_title'),
                'subtitle' => __('auth.login_subtitle'),
                'email' => __('auth.email'),
                'email_placeholder' => __('auth.email_placeholder'),
                'password' => __('auth.password'),
                'password_placeholder' => __('auth.password_placeholder'),
                'forgot_password' => __('auth.forgot_password'),
                'submit' => __('auth.login_button'),
                'new_to_recruivo' => __('auth.new_to_recruivo'),
                'create_account' => __('auth.create_account'),
                'email_verified' => __('auth.email_verified'),
                'account_created' => __('auth.account_created'),
                'toggle_password_visibility' => __('common.toggle_password_visibility'),
            ],
            'messages' => [
                'verified' => session('verified'),
                'registered' => session('registered'),
                'status' => session('status'),
                'info' => session('info'),
            ],
            'old' => [
                'email' => old('email', ''),
            ],
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::validate($credentials)) {
            return back()->withErrors([
                'email' => __('auth.invalid_credentials'),
            ])->withInput();
        }

        $user = User::where('email', $credentials['email'])->firstOrFail();

        if (! $user->hasVerifiedEmail()) {
            return back()->withErrors([
                'email' => __('auth.email_not_verified'),
            ])->withInput();
        }

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $user = Auth::user();
            if (! $user->last_login_at) {
                $request->session()->put('first_login', true);
            }

            $user->update(['last_login_at' => now()]);
            if ($user->hasRole('Admin')) {
                return redirect()->intended(localized_route('admin.dashboard'));
            }

            if ($user->hasRole('Recruiter')) {
                return redirect()->intended(localized_route('recruiter.dashboard'));
            }

            return redirect()->intended(localized_route('home'));
        }

        abort(500, 'Authentication validation succeeded but login failed.');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(localized_route('home'));
    }
}
