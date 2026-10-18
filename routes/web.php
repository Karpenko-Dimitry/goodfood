<?php

use App\Http\Controllers\DietController;
use App\Http\Controllers\DietPlanController;
use App\Http\Controllers\DishController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SeoController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('home', ['locale' => config('app.locale')]));

// Crawler files (public/robots.txt must not exist, or the web server would serve it instead).
Route::get('/robots.txt', [SeoController::class, 'robots']);
Route::get('/sitemap.xml', [SeoController::class, 'sitemap']);
Route::get('/llms.txt', [SeoController::class, 'llms']);

Route::prefix('{locale}')
    ->where(['locale' => implode('|', array_keys(config('app.locales')))])
    ->middleware(SetLocale::class)
    ->group(function () {
        Route::get('/', [PageController::class, 'home'])->name('home');
        Route::get('/about', [PageController::class, 'about'])->name('about');
        Route::get('/contact', [PageController::class, 'contact'])->name('contact');

        Route::get('/diets', [DietController::class, 'index'])->name('diets.index');
        Route::get('/diets/{diet}', [DietController::class, 'show'])->name('diets.show');

        Route::get('/recipes', [DishController::class, 'index'])->name('dishes.index');
        Route::get('/recipes/{dish}', [DishController::class, 'show'])->name('dishes.show');

        Route::get('/ai-plan', [DietPlanController::class, 'create'])->name('plan.create');
        Route::post('/ai-plan', [DietPlanController::class, 'store'])->middleware('throttle:5,10')->name('plan.store');
        Route::get('/ai-plan/{planRequest}', [DietPlanController::class, 'show'])->name('plan.show');
        Route::get('/ai-plan/{planRequest}/status', [DietPlanController::class, 'status'])->name('plan.status');
    });
