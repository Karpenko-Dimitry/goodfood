<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The admin panel keeps its own UI language (config app.admin_locale), independent of the site default.
 */
class SetAdminLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale(config('app.admin_locale'));

        return $next($request);
    }
}
