<?php

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $slug
 * @property string|null $name
 * @property int $sort
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Dish> $dishes
 * @property-read int|null $dishes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DishCategoryTranslation> $translations
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DishCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DishCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DishCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DishCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DishCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DishCategory whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DishCategory whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DishCategory whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class DishCategory extends Model implements TranslatableContract
{
    use Translatable;

    protected $guarded = [];

    /** Stored per locale in dish_category_translations. */
    public array $translatedAttributes = ['name'];

    /** Translations are needed on every render; avoid N+1. */
    protected $with = ['translations'];

    public function dishes(): HasMany
    {
        return $this->hasMany(Dish::class);
    }
}
