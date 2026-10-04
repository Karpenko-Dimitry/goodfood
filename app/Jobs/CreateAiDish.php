<?php

namespace App\Jobs;

use App\Jobs\Concerns\RetriesOrFails;
use App\Models\DietPlanRequest;
use App\Services\Ai\DishIdea;
use App\Services\Dishes\AiDishCreator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Generates and stores one new dish; optionally attaches it to the plan that asked for it.
 */
class CreateAiDish implements ShouldQueue
{
    use Queueable, RetriesOrFails;

    public int $tries = 2;

    public int $timeout = 240;

    public function __construct(public DishIdea $idea, public ?DietPlanRequest $planRequest = null) {}

    public function handle(AiDishCreator $creator): void
    {
        try {
            [$dish, $created] = $creator->create($this->idea);
        } catch (Throwable $e) {
            $this->retryOrFail($e);

            return;
        }

        if ($this->planRequest) {
            $this->planRequest->dishes()->syncWithoutDetaching([
                $dish->id => ['meal_title' => $this->idea->title, 'created' => $created],
            ]);
            $this->finish();
        }
    }

    public function failed(Throwable $e): void
    {
        report($e);
        $this->finish();
    }

    private function finish(): void
    {
        if ($this->planRequest) {
            DietPlanRequest::whereKey($this->planRequest->id)->where('dishes_pending', '>', 0)->decrement('dishes_pending');
        }
    }
}
