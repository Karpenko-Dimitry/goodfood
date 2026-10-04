<?php

namespace Database\Seeders;

use App\Models\Diet;
use App\Models\Dish;
use App\Models\DishCategory;
use Illuminate\Database\Seeder;

class DishSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect([
            'breakfast' => ['ru' => 'Завтраки', 'en' => 'Breakfast', 'uk' => 'Сніданки'],
            'lunch' => ['ru' => 'Обеды', 'en' => 'Lunch', 'uk' => 'Обіди'],
            'dinner' => ['ru' => 'Ужины', 'en' => 'Dinner', 'uk' => 'Вечері'],
            'salads' => ['ru' => 'Салаты', 'en' => 'Salads', 'uk' => 'Салати'],
            'snacks' => ['ru' => 'Перекусы', 'en' => 'Snacks', 'uk' => 'Перекуси'],
        ])->map(fn ($name, $slug) => DishCategory::updateOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'sort' => array_search($slug, ['breakfast', 'lunch', 'dinner', 'salads', 'snacks'])],
        ));

        $diets = Diet::pluck('id', 'slug');

        foreach (require __DIR__.'/data/dishes.php' as $data) {
            $dish = Dish::updateOrCreate(['slug' => $data['slug']], [
                'dish_category_id' => $categories[$data['category']]->id,
                'name' => $data['name'],
                'excerpt' => $data['excerpt'],
                'ingredients' => $data['ingredients'],
                'instructions' => $data['instructions'],
                'image_url' => $data['image_url'],
                'calories' => $data['calories'],
                'protein' => $data['protein'],
                'fat' => $data['fat'],
                'carbs' => $data['carbs'],
                'prep_minutes' => $data['prep'],
                'cook_minutes' => $data['cook'],
                'servings' => $data['servings'],
                'is_featured' => $data['featured'],
                'source' => 'seed',
            ]);

            $dish->diets()->sync($diets->only($data['diets'])->values());
        }
    }
}
