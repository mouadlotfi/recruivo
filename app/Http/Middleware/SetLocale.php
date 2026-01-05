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
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get locale from URL parameter
        $locale = $request->route('locale');
        
        // Get supported locales from config
        $supportedLocales = config('locales.supported', ['en', 'fr']);
        
        // Validate and set locale
        if ($locale && in_array($locale, $supportedLocales)) {
            App::setLocale($locale);
        } else {
            // Fall back to default locale from config
            App::setLocale(config('locales.default', config('app.locale')));
        }
        
        return $next($request);
    }
}
