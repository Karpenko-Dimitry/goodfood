<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

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
    }
}
