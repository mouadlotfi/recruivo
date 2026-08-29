<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale');
        $supportedLocales = config('locales.supported', ['en', 'fr']);

        if ($locale && in_array($locale, $supportedLocales)) {
            App::setLocale($locale);
        } else {
            App::setLocale(config('locales.default', config('app.locale')));
        }

        return $next($request);
    }
}
