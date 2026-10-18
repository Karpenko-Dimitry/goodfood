<?php

namespace App\Models;

use App\Models\Concerns\HasImage;
use App\Models\Concerns\SplitsLines;
use Illuminate\Database\Eloquent\Builder;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int|null $dish_category_id
 * @property string $slug
 * @property string|null $name
 * @property string|null $excerpt
 * @property string|null $ingredients
 * @property string|null $instructions
 * @property string|null $image
 * @property string|null $image_url
 * @property string|null $image_prompt
 * @property int $calories
 * @property float $protein
 * @property float $fat
 * @property float $carbs
 * @property int|null $prep_minutes
 * @property int|null $cook_minutes
 * @property int $servings
 * @property bool $is_featured
 * @property bool $is_published
 * @property string $source
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\DishCategory|null $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Diet> $diets
 * @property-read int|null $diets_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DishTranslation> $translations
 * @method static Builder<static>|Dish newModelQuery()
 * @method static Builder<static>|Dish newQuery()
 * @method static Builder<static>|Dish published()
 * @method static Builder<static>|Dish query()
 * @method static Builder<static>|Dish whereCalories($value)
 * @method static Builder<static>|Dish whereCarbs($value)
 * @method static Builder<static>|Dish whereCookMinutes($value)
 * @method static Builder<static>|Dish whereCreatedAt($value)
 * @method static Builder<static>|Dish whereDishCategoryId($value)
 * @method static Builder<static>|Dish whereFat($value)
 * @method static Builder<static>|Dish whereId($value)
 * @method static Builder<static>|Dish whereImage($value)
 * @method static Builder<static>|Dish whereImagePrompt($value)
 * @method static Builder<static>|Dish whereImageUrl($value)
 * @method static Builder<static>|Dish whereIsFeatured($value)
 * @method static Builder<static>|Dish whereIsPublished($value)
 * @method static Builder<static>|Dish wherePrepMinutes($value)
 * @method static Builder<static>|Dish whereProtein($value)
 * @method static Builder<static>|Dish whereServings($value)
 * @method static Builder<static>|Dish whereSlug($value)
 * @method static Builder<static>|Dish whereSource($value)
 * @method static Builder<static>|Dish whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Dish extends Model implements TranslatableContract
{
    use HasImage, SplitsLines, Translatable;

    protected $guarded = [];

    /** Stored per locale in dish_translations. */
    public array $translatedAttributes = ['name', 'excerpt', 'ingredients', 'instructions'];

    /** Translations are needed on every render; avoid N+1. */
    protected $with = ['translations', 'imageObject.files'];

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

    public static function imageFileConfig(): string
    {
        return 'dish_image';   // App\FileConfigs\DishImageFileConfig
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
     * Names of the latest dishes (English, or another locale when English is missing), for AI prompts.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    public static function recentNames(int $limit = 500, string $locale = 'en'): \Illuminate\Support\Collection
    {
        $ids = static::query()->latest('id')->limit($limit)->pluck('id');

        return DishTranslation::query()
            ->whereIn('dish_id', $ids)
            ->whereNotNull('name')
            ->get(['dish_id', 'locale', 'name'])
            ->groupBy('dish_id')
            ->map(fn ($translations) => ($translations->firstWhere('locale', $locale) ?? $translations->first())->name)
            ->values();
    }

    /**
     * A dish whose name in any locale matches the given title.
     */
    public static function findByTitle(string $title): ?self
    {
        $needle = static::normalizeTitle($title);

        $match = DishTranslation::query()->whereNotNull('name')->get(['dish_id', 'name'])
            ->first(fn (DishTranslation $t) => static::normalizeTitle($t->name) === $needle);

        return $match ? static::find($match->dish_id) : null;
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
