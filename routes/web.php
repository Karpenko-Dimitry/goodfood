<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
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

// Google OAuth: the callback URL must match the one registered in Google Cloud Console (no locale prefix).
Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');

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

        Route::middleware('guest')->group(function () {
            Route::get('/login', [LoginController::class, 'create'])->name('login');
            Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:20,1');
            Route::get('/register', [RegisterController::class, 'create'])->name('register');
            Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:10,1');
            Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
            Route::post('/forgot-password', [PasswordResetController::class, 'store'])->middleware('throttle:5,1')->name('password.email');
            Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
            Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.store');
        });

        Route::middleware('auth')->group(function () {
            Route::get('/account', [AccountController::class, 'index'])->name('account');
            Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
        });
    });
