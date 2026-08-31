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
        $locale = $request->route('locale') ?? $request->segment(1);
        $supportedLocales = config('locales.supported', ['en', 'fr']);

        if ($locale && in_array($locale, $supportedLocales, true)) {
            App::setLocale($locale);
        } else {
            $locale = config('locales.default', config('app.locale'));
            App::setLocale($locale);
        }

        $response = $next($request);

        if ($locale) {
            $response->headers->setCookie(cookie('locale', $locale, 60 * 24 * 365, '/', null, false, false));
        }

        return $response;
    }
}
