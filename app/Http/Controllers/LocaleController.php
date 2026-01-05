<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LocaleController extends Controller
{
    public function switch(Request $request, string $locale)
    {
        // Validate locale
        $supportedLocales = config('locales.supported', ['en', 'fr']);
        if (!in_array($locale, $supportedLocales)) {
            abort(400);
        }

        // Get current route name and parameters
        $currentRoute = $request->route();
        $currentLocale = $currentRoute->parameter('locale') ?? config('locales.default', config('app.locale'));
        
        // Get the URL from referer or previous URL
        $previousUrl = url()->previous();
        
        // Replace the locale in the URL
        $newUrl = str_replace('/' . $currentLocale . '/', '/' . $locale . '/', $previousUrl);
        
        // If no locale was in the URL, prepend it
        if ($newUrl === $previousUrl) {
            $path = parse_url($previousUrl, PHP_URL_PATH);
            $newUrl = url($locale . $path);
        }
        
        return redirect($newUrl);
    }
}
