<?php

namespace App\Providers;

use App\Support\DynamicVite;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Vite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Rewrites Vite's asset host to the request host, so the dev server works
        // from a LAN address as well as localhost.
        $this->app->singleton(Vite::class, DynamicVite::class);
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
        RateLimiter::for('job-apply', fn (Request $request) => Limit::perMinute(15)->by(
            $request->user()?->id ?: $request->ip()
        ));
        // Roomier than the rest: the autocomplete debounces at 180ms while typing.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by(
            $request->user()?->id ?: $request->ip()
        ));
        // Public form that sends mail.
        RateLimiter::for('contact', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
    }
}
