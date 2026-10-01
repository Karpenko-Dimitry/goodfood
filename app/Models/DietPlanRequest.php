<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

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
