<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One locale of a Diet (astrotomic/laravel-translatable).
 */
class DietTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'excerpt', 'description', 'pros', 'cons', 'allowed_foods', 'forbidden_foods'];
}
