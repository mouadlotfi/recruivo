<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $redirectToRoute
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, $redirectToRoute = null): Response
    {
        if (! $request->user() ||
            ($request->user() instanceof MustVerifyEmail &&
            ! $request->user()->hasVerifiedEmail())) {
            
            // Get the locale from the route parameter
            $locale = $request->route('locale') ?? app()->getLocale();
            
            return $request->expectsJson()
                    ? abort(403, __('auth.email_not_verified_api'))
                    : Redirect::route($redirectToRoute ?: 'verification.notice', ['locale' => $locale]);
        }

        return $next($request);
    }
}

