<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LocaleController extends Controller
{
    public function switch(Request $request, string $locale)
    {
        $supportedLocales = config('locales.supported', ['en', 'fr']);
        if (!in_array($locale, $supportedLocales)) {
            abort(400);
        }

        $currentRoute = $request->route();
        $currentLocale = $currentRoute->parameter('locale') ?? config('locales.default', config('app.locale'));
        $previousUrl = url()->previous();
        
        $newUrl = str_replace('/' . $currentLocale . '/', '/' . $locale . '/', $previousUrl);
        if ($newUrl === $previousUrl) {
            $path = parse_url($previousUrl, PHP_URL_PATH);
            $newUrl = url($locale . $path);
        }
        return redirect($newUrl);
    }
}
