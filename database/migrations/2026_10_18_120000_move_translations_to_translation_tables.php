<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Switch content translations from JSON columns (spatie/laravel-translatable)
 * to per-locale rows in *_translations tables (astrotomic/laravel-translatable).
 * Existing data is copied both ways, so the migration can be rolled back.
 */
return new class extends Migration
{
    /**
     * table => [translation table, foreign key, [column => type]]
     */
    private function map(): array
    {
        return [
            'diets' => ['diet_translations', 'diet_id', [
                'name' => 'string',
                'excerpt' => 'text',
                'description' => 'longText',
                'pros' => 'text',
                'cons' => 'text',
                'allowed_foods' => 'text',
                'forbidden_foods' => 'text',
            ]],
            'dishes' => ['dish_translations', 'dish_id', [
                'name' => 'string',
                'excerpt' => 'text',
                'ingredients' => 'text',
                'instructions' => 'text',
            ]],
            'dish_categories' => ['dish_category_translations', 'dish_category_id', [
                'name' => 'string',
            ]],
        ];
    }

    public function up(): void
    {
        foreach ($this->map() as $table => [$translationTable, $foreignKey, $columns]) {
            Schema::create($translationTable, function (Blueprint $t) use ($table, $foreignKey, $columns) {
                $t->id();
                $t->foreignId($foreignKey)->constrained($table)->cascadeOnDelete();
                $t->string('locale', 5)->index();
                foreach ($columns as $column => $type) {
                    $t->{$type}($column)->nullable();
                }
                $t->unique([$foreignKey, 'locale']);
            });

            DB::table($table)->orderBy('id')->each(function ($row) use ($translationTable, $foreignKey, $columns) {
                $byLocale = [];
                foreach (array_keys($columns) as $column) {
                    foreach (json_decode($row->{$column} ?? 'null', true) ?: [] as $locale => $value) {
                        $byLocale[$locale][$column] = $value;
                    }
                }

                foreach ($byLocale as $locale => $values) {
                    DB::table($translationTable)->insert([$foreignKey => $row->id, 'locale' => $locale] + $values);
                }
            });

            Schema::table($table, fn (Blueprint $t) => $t->dropColumn(array_keys($columns)));
        }
    }

    public function down(): void
    {
        foreach ($this->map() as $table => [$translationTable, $foreignKey, $columns]) {
            Schema::table($table, function (Blueprint $t) use ($columns) {
                foreach (array_keys($columns) as $column) {
                    $t->json($column)->nullable();
                }
            });

            DB::table($table)->orderBy('id')->each(function ($row) use ($table, $translationTable, $foreignKey, $columns) {
                $translations = DB::table($translationTable)->where($foreignKey, $row->id)->get();

                DB::table($table)->where('id', $row->id)->update(collect($columns)->keys()
                    ->mapWithKeys(fn ($column) => [$column => json_encode(
                        $translations->pluck($column, 'locale')->filter(fn ($v) => $v !== null)->all(),
                        JSON_UNESCAPED_UNICODE,
                    )])->all());
            });

            Schema::dropIfExists($translationTable);
        }
    }
};
