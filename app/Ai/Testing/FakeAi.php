<?php

namespace App\Ai\Testing;

use App\Ai\Contracts\ImageModel;
use App\Ai\Contracts\StructuredTask;
use App\Ai\Contracts\TextModel;
use App\Services\Ai\NutritionAi;
use Closure;
use Illuminate\Contracts\Foundation\Application;
use PHPUnit\Framework\Assert;
use RuntimeException;

/**
 * Test double for both AI providers: no network, scripted answers, recorded prompts.
 */
class FakeAi implements ImageModel, TextModel
{
    /** @var array<class-string, list<array|Closure>> */
    private array $responses = [];

    /** @var list<StructuredTask> */
    public array $tasks = [];

    /** @var list<string> */
    public array $imagePrompts = [];

    private ?Closure $image = null;

    public static function install(Application $app): self
    {
        $fake = new self;
        $app->instance(TextModel::class, $fake);
        $app->instance(ImageModel::class, $fake);
        $app->forgetInstance(NutritionAi::class);

        return $fake;
    }

    /**
     * Queue answers for a task class. A Closure receives the task and returns the array (or throws).
     * The last answer is reused once the queue is exhausted.
     */
    public function respond(string $taskClass, array|Closure ...$responses): self
    {
        $this->responses[$taskClass] = $responses;

        return $this;
    }

    public function images(Closure $generator): self
    {
        $this->image = $generator;

        return $this;
    }

    public function generate(StructuredTask|string $task, string $model): array
    {
        if (is_string($task)) {
            $this->imagePrompts[] = $task;

            return $this->image ? ($this->image)($task) : [self::png(), 'png'];
        }

        $this->tasks[] = $task;
        $queue = $this->responses[$task::class] ?? throw new RuntimeException('No fake response for '.$task::class);
        $response = count($queue) > 1 ? array_shift($this->responses[$task::class]) : $queue[0];

        return $response instanceof Closure ? $response($task) : $response;
    }

    /**
     * A real (tiny) PNG, so image processing (FileService presets) can decode it.
     */
    public static function png(int $width = 1600, int $height = 1200): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 65, 117, 0));
        ob_start();
        imagepng($image);
        imagedestroy($image);

        return (string) ob_get_clean();
    }

    public function available(): bool
    {
        return true;
    }

    public function assertPrompted(string $taskClass, ?Closure $callback = null): void
    {
        Assert::assertNotEmpty(
            array_filter($this->tasks, fn ($t) => $t instanceof $taskClass && (! $callback || $callback($t))),
            "Expected {$taskClass} to be prompted.",
        );
    }

    public function assertPromptedTimes(string $taskClass, int $times): void
    {
        Assert::assertCount($times, array_filter($this->tasks, fn ($t) => $t instanceof $taskClass));
    }

    public function assertImageGenerated(Closure $callback): void
    {
        Assert::assertNotEmpty(array_filter($this->imagePrompts, $callback), 'Expected an image to be generated.');
    }
}
