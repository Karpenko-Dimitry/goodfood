<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\App\Ai\Contracts\TextModel::class, fn () => match (config('services.ai.text_provider')) {
            'anthropic' => new \App\Ai\Providers\ClaudeTextModel(
                new \Anthropic\Client(apiKey: config('services.ai.anthropic_key')),
                config('services.ai.effort'),
                config('services.ai.anthropic_key'),
            ),
            default => new \App\Ai\Providers\OpenAiTextModel(
                \OpenAI::client((string) config('services.ai.openai_key')),
                config('services.ai.openai_key'),
            ),
        });
        $this->app->bind(\App\Ai\Contracts\ImageModel::class, fn () => new \App\Ai\Providers\OpenAiImageModel(config('services.ai.openai_key')));
        $this->app->singleton(\App\Services\Ai\NutritionAi::class, fn ($app) => new \App\Services\Ai\NutritionAi(
            $app->make(\App\Ai\Contracts\TextModel::class),
            $app->make(\App\Ai\Contracts\ImageModel::class),
            config('services.ai'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // public/hot is written by `npm run dev`; if it is ever uploaded to the server, pages would load
        // CSS/JS from the developer's machine (http://[::1]:5173). Production always uses public/build.
        if ($this->app->isProduction()) {
            Vite::useHotFile(storage_path('framework/vite.hot.disabled'));
        }
    }
}
