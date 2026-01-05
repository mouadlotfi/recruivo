<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Scout\Scout;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Scout configuration for local environment
        if (app()->environment('local') && config('scout.driver') === 'null') {
            // Disable Scout indexing in local environment when using null driver
            // This prevents unnecessary indexing operations during development
        }

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
