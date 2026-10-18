<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property string $locale
 * @property string|null $name
 * @property string|null $email
 * @property string $gender
 * @property int $age
 * @property int $height_cm
 * @property float $weight_kg
 * @property float|null $target_weight_kg
 * @property string $activity
 * @property string $goal
 * @property int|null $preferred_diet_id
 * @property string|null $preferences
 * @property string|null $allergies
 * @property int $meals_per_day
 * @property string $status
 * @property int $dishes_pending
 * @property array<array-key, mixed>|null $result
 * @property string|null $error
 * @property string|null $ip
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Dish> $dishes
 * @property-read int|null $dishes_count
 * @property-read \App\Models\Diet|null $preferredDiet
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DietPlanRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DietPlanRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DietPlanRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DietPlanRequest whereActivity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DietPlanRequest whereAge($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DietPlanRequest whereAllergies($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DietPlanRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DietPlanRequest whereDishesPending($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DietPlanRequest whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DietPlanRequest whereError($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DietPlanRequest whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DietPlanRequest whereGoal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DietPlanRequest whereHeightCm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DietPlanRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DietPlanRequest whereIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DietPlanRequest whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DietPlanRequest whereMealsPerDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DietPlanRequest whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DietPlanRequest wherePreferences($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DietPlanRequest wherePreferredDietId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DietPlanRequest whereResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DietPlanRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DietPlanRequest whereTargetWeightKg($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DietPlanRequest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DietPlanRequest whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DietPlanRequest whereWeightKg($value)
 * @mixin \Eloquent
 */
class DietPlanRequest extends Model
{
    public const GOALS = ['lose', 'maintain', 'gain', 'health'];

    public const ACTIVITIES = ['sedentary', 'light', 'moderate', 'active', 'very_active'];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'result' => 'array',
            'weight_kg' => 'float',
            'target_weight_kg' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $request) => $request->uuid ??= (string) Str::uuid());
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function preferredDiet(): BelongsTo
    {
        return $this->belongsTo(Diet::class, 'preferred_diet_id');
    }

    /**
     * Dishes the plan's meals resolved to (pivot: meal_title, created).
     */
    public function dishes(): BelongsToMany
    {
        return $this->belongsToMany(Dish::class)->withPivot('meal_title', 'created');
    }

    public function bmi(): float
    {
        return round($this->weight_kg / (($this->height_cm / 100) ** 2), 1);
    }

    /**
     * Mifflin–St Jeor BMR × activity factor, adjusted for the goal.
     */
    public function targetCalories(): int
    {
        $bmr = 10 * $this->weight_kg + 6.25 * $this->height_cm - 5 * $this->age + ($this->gender === 'male' ? 5 : -161);

        $factor = match ($this->activity) {
            'sedentary' => 1.2,
            'light' => 1.375,
            'moderate' => 1.55,
            'active' => 1.725,
            default => 1.9,
        };

        $adjust = match ($this->goal) {
            'lose' => 0.8,
            'gain' => 1.15,
            default => 1.0,
        };

        return (int) (round($bmr * $factor * $adjust / 10) * 10);
    }
}
