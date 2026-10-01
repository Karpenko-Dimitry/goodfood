<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Spatie\TranslationLoader\LanguageLine;

class ImportTranslations extends Command
{
    protected $signature = 'translations:import
                            {--group=site : Translation group, i.e. the lang file name}
                            {--force : Overwrite texts already edited in the database}';

    protected $description = 'Copy UI strings from lang files into the language_lines table (spatie/laravel-translation-loader)';

    public function handle(): int
    {
        $group = $this->option('group');
        $lines = [];

        foreach (array_keys(config('app.locales')) as $locale) {
            $file = lang_path("{$locale}/{$group}.php");

            if (! is_file($file)) {
                continue;
            }

            foreach (Arr::dot(require $file) as $key => $text) {
                $lines[$key][$locale] = $text;
            }
        }

        $created = $updated = 0;

        foreach ($lines as $key => $texts) {
            $line = LanguageLine::firstOrNew(['group' => $group, 'key' => $key]);

            if (! $line->exists) {
                $line->text = $texts;
                $line->save();
                $created++;

                continue;
            }

            // Only fill in missing locales unless --force.
            $merged = $this->option('force') ? array_merge($line->text ?? [], $texts) : array_merge($texts, $line->text ?? []);

            if ($merged != $line->text) {
                $line->text = $merged;
                $line->save();
                $updated++;
            }
        }

        $this->info("Group [{$group}]: {$created} created, {$updated} updated.");

        return self::SUCCESS;
    }
}
