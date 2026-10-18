<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@dietcoach.test'],
            ['name' => 'Admin', 'password' => 'password'],
        );

        $this->call([
            DietSeeder::class,
            DishSeeder::class,
            LanguageLineSeeder::class,
        ]);

        // Copy the demo photos from Unsplash into local storage (skipped in tests: no network there).
        if (! app()->runningUnitTests()) {
            $this->command?->info('Downloading demo images to storage/app/public…');
            Artisan::call('images:localize', [], $this->command?->getOutput());
        }
    }
}
