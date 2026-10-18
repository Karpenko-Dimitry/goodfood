<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Responsive variants of external images (Unsplash resizes via query parameters).
 */
class ExternalImage
{
    public static function resized(string $url, int $width): string
    {
        if (! self::isResizable($url)) {
            return $url;
        }

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return Str::before($url, '?').'?'.http_build_query(['w' => $width, 'q' => 70, 'auto' => 'format', 'fit' => 'crop'] + $query);
    }

    /**
     * @param  list<int>  $widths
     */
    public static function srcset(string $url, array $widths = [480, 960, 1440]): ?string
    {
        if (! self::isResizable($url)) {
            return null;
        }

        $base = Str::before($url, '?');

        return collect($widths)->map(fn (int $w) => self::resized($base, $w).' '.$w.'w')->implode(', ');
    }

    private static function isResizable(string $url): bool
    {
        return Str::contains($url, 'images.unsplash.com');
    }
}
