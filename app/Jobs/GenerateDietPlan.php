<?php

namespace App\Jobs;

use App\Ai\Agents\DietPlanner;
use App\Models\DietPlanRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateDietPlan implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 240;

    public function __construct(public DietPlanRequest $planRequest) {}

    public function handle(): void
    {
        $agent = new DietPlanner($this->planRequest);

        $response = $agent->prompt($agent->brief(), model: config('services.openai.diet_model'));

        $this->planRequest->update([
            'status' => 'completed',
            'result' => $response->toArray(),
            'error' => null,
        ]);
    }

    public function failed(Throwable $e): void
    {
        $this->planRequest->update(['status' => 'failed', 'error' => $e->getMessage()]);
    }
}
