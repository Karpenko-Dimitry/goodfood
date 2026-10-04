<?php

namespace App\Models;

use App\Models\Concerns\HasImage;
use App\Models\Concerns\SplitsLines;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Dish extends Model
{
    use HasImage, HasTranslations, SplitsLines;

    protected $guarded = [];

    public array $translatable = ['name', 'excerpt', 'ingredients', 'instructions'];

    protected function casts(): array
    {
        return [
            'protein' => 'float',
            'fat' => 'float',
            'carbs' => 'float',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DishCategory::class, 'dish_category_id');
    }

    public function diets(): BelongsToMany
    {
        return $this->belongsToMany(Diet::class);
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    public function isAiGenerated(): bool
    {
        return $this->source === 'ai';
    }

    /**
     * Comparable form of a dish title: lowercase, no punctuation, single spaces.
     */
    public static function normalizeTitle(string $title): string
    {
        return Str::of($title)->lower()->replaceMatches('/[^\p{L}\p{N}]+/u', ' ')->squish()->toString();
    }

    /**
     * A dish whose name in any locale matches the given title.
     */
    public static function findByTitle(string $title): ?self
    {
        $needle = static::normalizeTitle($title);

        return static::query()->get(['id', 'slug', 'name'])
            ->first(fn (self $dish) => collect($dish->getTranslations('name'))
                ->contains(fn ($name) => static::normalizeTitle((string) $name) === $needle));
    }

    public function totalMinutes(): int
    {
        return (int) $this->prep_minutes + (int) $this->cook_minutes;
    }

    /**
     * Share of calories coming from each macro (4/9/4 kcal per gram).
     *
     * @return array{protein: int, fat: int, carbs: int}
     */
    public function macroSplit(): array
    {
        $kcal = [$this->protein * 4, $this->fat * 9, $this->carbs * 4];
        $sum = array_sum($kcal) ?: 1;

        return [
            'protein' => (int) round($kcal[0] / $sum * 100),
            'fat' => (int) round($kcal[1] / $sum * 100),
            'carbs' => (int) round($kcal[2] / $sum * 100),
        ];
    }
}
