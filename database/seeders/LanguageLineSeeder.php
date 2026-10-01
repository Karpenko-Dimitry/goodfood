<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class LanguageLineSeeder extends Seeder
{
    /**
     * Copy UI strings from lang/*.php into the language_lines table so they
     * become editable in the admin panel.
     */
    public function run(): void
    {
        Artisan::call('translations:import');
    }
}
