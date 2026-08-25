<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\StrongPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PasswordResetController extends Controller
{
    /**
     * Display the password reset request form.
     */
    public function showResetForm()
    {
        return Inertia::render('Auth/ForgotPassword', [
            'labels' => [
                'title' => __('auth.forgot_password_title'),
                'description' => __('auth.forgot_password_desc'),
                'email' => __('auth.email'),
                'email_placeholder' => __('auth.email_placeholder'),
                'submit' => __('auth.email_reset_link'),
                'back_to_login' => __('auth.back_to_login'),
                'create_account' => __('auth.create_account'),
            ],
            'old_email' => old('email', ''),
            'messages' => ['status' => session('status')],
        ]);
    }

    /**
     * Send a password reset link to the user.
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    /**
     * Display the password reset form.
     */
    public function showResetPasswordForm($token)
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => request('email', old('email', '')),
            'labels' => [
                'title' => __('auth.set_new_password'),
                'description' => __('auth.enter_new_password'),
                'email' => __('auth.email'),
                'password' => __('auth.new_password'),
                'password_placeholder' => __('auth.new_password_placeholder'),
                'password_confirmation' => __('auth.password_confirmation'),
                'password_confirmation_placeholder' => __('auth.confirm_password_placeholder'),
                'submit' => __('auth.reset_password_button'),
                'back_to_login' => __('auth.back_to_login'),
                'toggle_password_visibility' => __('common.toggle_password_visibility'),
            ],
        ]);
    }

    /**
     * Reset the user's password.
     */
    public function reset(Request $request)
    {
        $email = $request->input('email');
        $username = $email ? explode('@', $email)[0] : null;
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', new StrongPassword($username)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect(localized_route('login'))->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }
}

