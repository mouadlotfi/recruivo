<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        ResetPasswordNotification::createUrlUsing(fn ($user, string $token): string => route('password.reset', [
            'locale' => app()->getLocale() ?: config('locales.default', 'en'),
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ]));

        RateLimiter::for('auth-login', fn (Request $request) => Limit::perMinute(10)->by(
            Str::lower((string) $request->input('email')).'|'.$request->ip()
        ));
        RateLimiter::for('auth-register', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('verification-email', fn (Request $request) => Limit::perMinute(6)->by(
            Str::lower((string) $request->input('email')).'|'.$request->ip()
        ));
        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(6)->by(
            Str::lower((string) $request->input('email')).'|'.$request->ip()
        ));

        // Set default password rules (fallback for places not using StrongPassword rule)
        Password::defaults(function () {
            return Password::min(12)
                ->max(64)
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised();
        });
    }
}
