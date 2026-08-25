<?php

if (!function_exists('localized_route')) {
    /**
     * Generate a localized route URL.
     *
     * @param string $name
     * @param mixed $parameters
     * @param string|null $locale
     * @return string
     */
    function localized_route(string $name, $parameters = [], ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        
        if (!is_array($parameters)) {
            $parameters = [$parameters];
        }
        
        $isAssoc = !empty($parameters) && (array_keys($parameters) !== range(0, count($parameters) - 1));
        
        if ($isAssoc) {
            $parameters = ['locale' => $locale] + $parameters;
        } else {
            array_unshift($parameters, $locale);
        }
        
        return route($name, $parameters);
    }
}

