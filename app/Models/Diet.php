<?php

namespace App\Models;

use App\Models\Concerns\HasImage;
use App\Models\Concerns\SplitsLines;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

class Diet extends Model
{
    use HasImage, HasTranslations, SplitsLines;

    protected $guarded = [];

    public array $translatable = ['name', 'excerpt', 'description', 'pros', 'cons', 'allowed_foods', 'forbidden_foods'];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function dishes(): BelongsToMany
    {
        return $this->belongsToMany(Dish::class);
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true)->orderBy('sort')->orderBy('id');
    }
}
