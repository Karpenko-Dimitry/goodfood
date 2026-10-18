<?php

namespace App\Models;

use App\Models\Concerns\HasImage;
use App\Models\Concerns\SplitsLines;
use Illuminate\Database\Eloquent\Builder;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $slug
 * @property string|null $name
 * @property string|null $excerpt
 * @property string|null $description
 * @property string|null $pros
 * @property string|null $cons
 * @property string|null $allowed_foods
 * @property string|null $forbidden_foods
 * @property string|null $image
 * @property string|null $image_url
 * @property string|null $icon
 * @property int $difficulty
 * @property int|null $duration_days
 * @property int|null $calories_min
 * @property int|null $calories_max
 * @property int|null $protein_pct
 * @property int|null $fat_pct
 * @property int|null $carbs_pct
 * @property bool $is_featured
 * @property bool $is_published
 * @property int $sort
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Dish> $dishes
 * @property-read int|null $dishes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DietTranslation> $translations
 * @method static Builder<static>|Diet newModelQuery()
 * @method static Builder<static>|Diet newQuery()
 * @method static Builder<static>|Diet published()
 * @method static Builder<static>|Diet query()
 * @method static Builder<static>|Diet whereCaloriesMax($value)
 * @method static Builder<static>|Diet whereCaloriesMin($value)
 * @method static Builder<static>|Diet whereCarbsPct($value)
 * @method static Builder<static>|Diet whereCreatedAt($value)
 * @method static Builder<static>|Diet whereDifficulty($value)
 * @method static Builder<static>|Diet whereDurationDays($value)
 * @method static Builder<static>|Diet whereFatPct($value)
 * @method static Builder<static>|Diet whereIcon($value)
 * @method static Builder<static>|Diet whereId($value)
 * @method static Builder<static>|Diet whereImage($value)
 * @method static Builder<static>|Diet whereImageUrl($value)
 * @method static Builder<static>|Diet whereIsFeatured($value)
 * @method static Builder<static>|Diet whereIsPublished($value)
 * @method static Builder<static>|Diet whereProteinPct($value)
 * @method static Builder<static>|Diet whereSlug($value)
 * @method static Builder<static>|Diet whereSort($value)
 * @method static Builder<static>|Diet whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Diet extends Model implements TranslatableContract
{
    use HasImage, SplitsLines, Translatable;

    protected $guarded = [];

    /** Stored per locale in diet_translations. */
    public array $translatedAttributes = ['name', 'excerpt', 'description', 'pros', 'cons', 'allowed_foods', 'forbidden_foods'];

    /** Translations are needed on every render; avoid N+1. */
    protected $with = ['translations'];

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
