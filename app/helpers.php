<?php

if (! function_exists('localized_url')) {
    /**
     * URL of the current page in another locale (used by the language switcher).
     */
    function localized_url(string $locale): string
    {
        $route = request()->route();

        if (! $route?->getName() || ! in_array('locale', $route->parameterNames())) {
            return route('home', ['locale' => $locale]);
        }

        $url = route($route->getName(), array_merge($route->parameters(), ['locale' => $locale]));
        $query = request()->getQueryString();

        return $query ? "{$url}?{$query}" : $url;
    }
}
