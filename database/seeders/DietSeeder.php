<?php

namespace Database\Seeders;

use App\Models\Diet;
use App\Support\Translations;
use Illuminate\Database\Seeder;

class DietSeeder extends Seeder
{
    public function run(): void
    {
        foreach (require __DIR__.'/data/diets.php' as $diet) {
            Diet::updateOrCreate(['slug' => $diet['slug']], Translations::byLocale($diet, (new Diet)->translatedAttributes));
        }
    }
}
