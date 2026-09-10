<?php

use Illuminate\Pagination\LengthAwarePaginator;

if (! function_exists('localized_route')) {
    /**
     * Generate a localized route URL.
     *
     * @param  mixed  $parameters
     */
    function localized_route(string $name, $parameters = [], ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        if (! is_array($parameters)) {
            $parameters = [$parameters];
        }

        $isAssoc = ! empty($parameters) && (array_keys($parameters) !== range(0, count($parameters) - 1));

        if ($isAssoc) {
            $parameters = ['locale' => $locale] + $parameters;
        } else {
            array_unshift($parameters, $locale);
        }

        return route($name, $parameters);
    }
}

if (! function_exists('pagination_payload')) {
    /**
     * Flat pagination payload shared by every paginated Inertia page.
     *
     * @return array<string, mixed>
     */
    function pagination_payload(LengthAwarePaginator $paginator): array
    {
        return [
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'next_page_url' => $paginator->nextPageUrl(),
            'prev_page_url' => $paginator->previousPageUrl(),
        ];
    }
}
