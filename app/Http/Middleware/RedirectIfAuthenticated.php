<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();
                
                // Get locale from route parameter or use default
                $locale = $request->route('locale') ?? config('locales.default', config('app.locale'));
                
                // Redirect based on user role with proper locale
                if ($user->hasRole('Admin')) {
                    return redirect(localized_route('admin.dashboard', [], $locale));
                } elseif ($user->hasRole('Recruiter')) {
                    return redirect(localized_route('recruiter.dashboard', [], $locale));
                } else {
                    return redirect(localized_route('candidate.dashboard', [], $locale));
                }
            }
        }

        return $next($request);
    }
}
