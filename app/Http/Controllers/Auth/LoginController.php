<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::validate($credentials)) {
            return back()->withErrors([
                'email' => __('auth.invalid_credentials'),
            ])->withInput();
        }

        $user = \App\Models\User::where('email', $credentials['email'])->firstOrFail();

        if (!$user->hasVerifiedEmail()) {
            return back()->withErrors([
                'email' => __('auth.email_not_verified'),
            ])->withInput();
        }

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $user = Auth::user();
            
            // Check if this is the first login (no last_login_at set)
            if (!$user->last_login_at) {
                $request->session()->put('first_login', true);
            }
            
            // Update last login timestamp
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

