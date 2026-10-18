<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Visitor;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Live counters from the visitors table (dashboard + Visitors page).
 */
class VisitorsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -1;

    protected ?string $pollingInterval = '30s';

    protected ?string $heading = 'Посетители';

    protected function getStats(): array
    {
        $online = Visitor::online();
        $onlineUsers = (clone $online)->registered()->count();
        $onlineGuests = (clone $online)->guests()->count();

        // New visitors per day for the last 7 days (sparkline).
        $trend = collect(range(6, 0))
            ->map(fn (int $days) => Visitor::whereDate('first_seen_at', today()->subDays($days))->count())
            ->all();

        return [
            Stat::make('Онлайн сейчас', $onlineUsers + $onlineGuests)
                ->description("пользователей: {$onlineUsers} · гостей: {$onlineGuests}")
                ->descriptionIcon('heroicon-m-signal')
                ->color('success'),
            Stat::make('Новые посетители сегодня', Visitor::newSince(today())->count())
                ->description('всего за сегодня: '.Visitor::where('last_seen_at', '>=', today())->count())
                ->chart($trend)
                ->color('primary'),
            Stat::make('Новые пользователи за 7 дней', User::where('created_at', '>=', now()->subDays(7))->count())
                ->description('всего пользователей: '.User::count()),
        ];
    }
}
