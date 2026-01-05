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
        
        // Handle different parameter formats
        if (!is_array($parameters)) {
            // Single parameter (model, int, string, etc.) - convert to indexed array
            $parameters = [$parameters];
        }
        
        // Check if array is associative or indexed
        $isAssoc = !empty($parameters) && (array_keys($parameters) !== range(0, count($parameters) - 1));
        
        if ($isAssoc) {
            // Associative array - add locale key at the beginning
            $parameters = ['locale' => $locale] + $parameters;
        } else {
            // Indexed array or empty - prepend locale
            array_unshift($parameters, $locale);
        }
        
        return route($name, $parameters);
    }
}

