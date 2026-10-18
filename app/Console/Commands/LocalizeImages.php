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
 * Builds FileService presets (App\FileConfigs) for diets and dishes that do not have them yet:
 * from a legacy local file in `image` (public disk) or by downloading `image_url`.
 */
class LocalizeImages extends Command
{
    protected $signature = 'images:localize
                            {--force : Rebuild presets even if the model already has them}';

    protected $description = 'Store diet/dish images locally as optimized presets (FileService)';

    private const MIME_EXTENSIONS = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];

    public function handle(): int
    {
        $done = $failed = 0;

        foreach ([Diet::class, Dish::class] as $model) {
            $records = $model::query()
                ->where(fn ($q) => $q->whereNotNull('image')->orWhereNotNull('image_url'))
                ->when(! $this->option('force'), fn ($q) => $q->whereDoesntHave('imageObject'))
                ->get();

            foreach ($records as $record) {
                try {
                    $this->localize($record);
                    $done++;
                    $this->line("  ✓ {$record->getTable()}/{$record->slug}");
                } catch (Throwable $e) {
                    $failed++;
                    $this->warn("  ✗ {$record->getTable()}/{$record->slug}: {$e->getMessage()}");
                }
            }
        }

        $this->info("Images stored as presets: {$done}, failed: {$failed}.");

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function localize(Model $record): void
    {
        $disk = Storage::disk('public');

        // A legacy local file (uploaded or downloaded earlier).
        if ($record->image && $disk->exists($record->image)) {
            $record->attachImage($disk->path($record->image), basename($record->image));
            $disk->delete($record->image);
            $record->newQueryWithoutScopes()->whereKey($record->getKey())->update(['image' => null]);

            return;
        }

        if (! $record->image_url) {
            throw new \RuntimeException('No image source.');
        }

        // Download the original (no resize parameters) into a temporary file.
        $response = Http::timeout(60)
            ->retry(2, 500, fn (Throwable $e) => $e instanceof ConnectionException
                || ($e instanceof RequestException && $e->response->serverError()))
            ->get($record->image_url)
            ->throw();

        $mime = strtok((string) $response->header('Content-Type'), ';');
        $extension = self::MIME_EXTENSIONS[$mime] ?? throw new \RuntimeException("Not an image ({$mime})");

        $tmp = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($tmp, $response->body());

        try {
            $record->attachImage($tmp, "{$record->slug}.{$extension}");
        } finally {
            @unlink($tmp);
        }
    }
}
