<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\TrackVisitor::class,
        ]);

        // Auth pages live under /{locale}/…: keep the visitor's language on auth redirects.
        $locale = fn (\Illuminate\Http\Request $request) => $request->route('locale') ?? app()->getLocale();
        $middleware->redirectGuestsTo(fn ($request) => route('login', ['locale' => $locale($request)]));
        $middleware->redirectUsersTo(fn ($request) => route('account', ['locale' => $locale($request)]));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
