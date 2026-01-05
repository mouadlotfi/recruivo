<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    protected function redirectTo($request)
    {
        if (!$request->expectsJson()) {
            // For email verification, redirect to a success page or return JSON
            if ($request->is('email/verify*')) {
                return url('/email/verified');
            }
            
            // For other web requests, redirect to frontend login
            return config('app.frontend_url', 'http://localhost:3000') . '/login';
        }

        return null;
    }
}
