<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One locale of a DishCategory (astrotomic/laravel-translatable).
 */
class DishCategoryTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['name'];
}
