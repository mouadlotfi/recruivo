<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

class LocaleController extends Controller
{
    public function switch(Request $request, string $locale): RedirectResponse
    {
        $supportedLocales = config('locales.supported', ['en', 'fr']);
        if (! in_array($locale, $supportedLocales)) {
            abort(400);
        }

        // The Vue language toggle rebuilds URLs client-side and never hits
        // this route — it survives for stale links/bookmarks. Rebuild the
        // previous page's route in the target locale so query strings and
        // slugs containing a locale word are never corrupted.
        $previousUrl = url()->previous();
        $previousRequest = Request::create($previousUrl);
        $previousRoute = RouteFacade::getRoutes()->match($previousRequest);

        $newUrl = $previousRoute->getName() !== null && $previousRoute->hasParameter('locale')
            ? $this->rebuildUrl($previousRoute, $previousRequest, $locale)
            : $this->swapPathLocale($previousRequest, $locale);

        return redirect($newUrl);
    }

    /**
     * Regenerate the matched localized route with the target locale,
     * preserving the previous request's query string verbatim.
     */
    private function rebuildUrl(Route $route, Request $previousRequest, string $locale): string
    {
        $parameters = $route->parameters();
        $parameters['locale'] = $locale;

        $url = route($route->getName(), $parameters);

        return $this->withQueryString($url, $previousRequest);
    }

    /**
     * Fallback for previous pages outside the localized web surface (root,
     * API, unknown paths): swap only the leading locale segment — never the
     * query string — or prefix the locale when the path has no segment.
     */
    private function swapPathLocale(Request $previousRequest, string $locale): string
    {
        $path = $previousRequest->path();

        if ($path === '' || $path === '/') {
            $url = url('/'.$locale);
        } else {
            $segments = explode('/', $path);
            $segments[0] = in_array($segments[0], config('locales.supported', ['en', 'fr']), true)
                ? $locale
                : $locale.'/'.$segments[0];
            $url = url(implode('/', $segments));
        }

        return $this->withQueryString($url, $previousRequest);
    }

    private function withQueryString(string $url, Request $previousRequest): string
    {
        return $previousRequest->getQueryString() ? $url.'?'.$previousRequest->getQueryString() : $url;
    }
}
