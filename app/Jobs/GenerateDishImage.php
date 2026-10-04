<?php

namespace App\Jobs;

use App\Jobs\Concerns\RetriesOrFails;
use App\Models\Dish;
use App\Services\Ai\NutritionAi;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateDishImage implements ShouldQueue
{
    use Queueable, RetriesOrFails;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(public Dish $dish) {}

    public function handle(NutritionAi $ai): void
    {
        $subject = $this->dish->image_prompt ?: $this->dish->getTranslation('name', 'en');

        try {
            $this->dish->update(['image' => $ai->dishImage($subject)]);
        } catch (Throwable $e) {
            $this->retryOrFail($e);
        }
    }

    /**
     * The dish stays without a photo; it can be generated later from the admin panel.
     */
    public function failed(Throwable $e): void
    {
        report($e);
    }
}
