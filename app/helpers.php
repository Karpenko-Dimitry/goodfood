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

if (! function_exists('get_objects_in_directory')) {
    /**
     * Instantiate every concrete class found in a directory (used by FileService to register file configs).
     *
     * @return list<object>
     */
    function get_objects_in_directory(string $directory, string $namespace): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        return collect(\Illuminate\Support\Facades\File::files($directory))
            ->map(fn (\SplFileInfo $file) => $namespace.$file->getBasename('.php'))
            ->filter(fn (string $class) => class_exists($class) && (new ReflectionClass($class))->isInstantiable())
            ->map(fn (string $class) => new $class)
            ->values()
            ->all();
    }
}

