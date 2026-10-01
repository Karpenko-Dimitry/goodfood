<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class DishCategory extends Model
{
    use HasTranslations;

    protected $guarded = [];

    public array $translatable = ['name'];

    public function dishes(): HasMany
    {
        return $this->hasMany(Dish::class);
    }
}
