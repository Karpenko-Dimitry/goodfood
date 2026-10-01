<?php

namespace App\Filament\Widgets;

use App\Models\Diet;
use App\Models\DietPlanRequest;
use App\Models\Dish;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Spatie\TranslationLoader\LanguageLine;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $plans = DietPlanRequest::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $trend = collect(range(6, 0))
            ->map(fn ($days) => DietPlanRequest::whereDate('created_at', today()->subDays($days))->count())
            ->all();

        return [
            Stat::make('Диеты', Diet::count())->description(Diet::where('is_published', true)->count().' опубликовано')->color('success'),
            Stat::make('Блюда', Dish::count())->description('с рецептами и КБЖУ'),
            Stat::make('AI-планы', $plans->sum())
                ->description(($plans['completed'] ?? 0).' готово · '.($plans['failed'] ?? 0).' ошибок')
                ->chart($trend)->color('primary'),
            Stat::make('Переводы', LanguageLine::count())->description(count(config('app.locales')).' языка'),
        ];
    }
}
