<?php

namespace App\Console\Commands;

use App\Models\Diet;
use App\Models\Dish;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Downloads external images (image_url) of diets and dishes into local storage (public disk)
 * so the site no longer hotlinks them. image_url is kept as the source reference.
 */
class LocalizeImages extends Command
{
    protected $signature = 'images:localize
                            {--force : Download again even if a local file is already set}';

    protected $description = 'Download external diet/dish images into storage/app/public';

    private const MIME_EXTENSIONS = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/avif' => 'avif'];

    public function handle(): int
    {
        $done = $failed = 0;

        foreach ([Diet::class => 'diets', Dish::class => 'dishes'] as $model => $directory) {
            $records = $model::query()
                ->whereNotNull('image_url')
                ->when(! $this->option('force'), fn ($q) => $q->whereNull('image'))
                ->get();

            foreach ($records as $record) {
                try {
                    $this->localize($record, $directory);
                    $done++;
                    $this->line("  ✓ {$directory}/{$record->slug}");
                } catch (Throwable $e) {
                    $failed++;
                    $this->warn("  ✗ {$directory}/{$record->slug}: {$e->getMessage()}");
                }
            }
        }

        $this->info("Images stored locally: {$done}, failed: {$failed}.");

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function localize(Model $record, string $directory): void
    {
        $response = Http::timeout(30)
            ->retry(2, 500, fn (Throwable $e) => $e instanceof ConnectionException
                || ($e instanceof RequestException && $e->response->serverError()))
            ->get($record->image_url)
            ->throw();

        $mime = strtok((string) $response->header('Content-Type'), ';');
        $extension = self::MIME_EXTENSIONS[$mime] ?? throw new \RuntimeException("Not an image ({$mime})");

        $path = "{$directory}/{$record->slug}.{$extension}";
        Storage::disk('public')->put($path, $response->body());

        $old = $record->image;
        $record->update(['image' => $path]);

        if ($old && $old !== $path) {
            Storage::disk('public')->delete($old);
        }
    }
}
