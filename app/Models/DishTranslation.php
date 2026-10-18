<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One locale of a Dish (astrotomic/laravel-translatable).
 */
class DishTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'excerpt', 'ingredients', 'instructions'];
}
