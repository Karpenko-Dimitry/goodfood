<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale');

        abort_unless(array_key_exists($locale, config('app.locales')), 404);

        app()->setLocale($locale);
        URL::defaults(['locale' => $locale]);

        // Controllers don't need the locale argument.
        $request->route()->forgetParameter('locale');

        return $next($request);
    }
}
