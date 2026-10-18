<?php

namespace App\Services\FileService\Console\Commands;

use App\Services\FileService\Models\File;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class UpdateFilePropertiesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'file:update-properties
                            {--refresh : Update properties even if they are already calculated.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update files: width, height, extension and file size fields.';

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle(): void
    {
        $successfulResults = 0;
        $query = $this->getQuery();

        if (!$allResults = (clone $query)->count()) {
            exit("√ No files found.\n");
        }

        /** @var File $file */
        foreach ($query->get() as $file) {
            if ($file->fileExists()) {
                $file->update([
                    'ext' => strtolower(pathinfo($file->path, PATHINFO_EXTENSION)),
                    'size' => $file->getSize(),
                ]);

                $successfulResults++;
            }
        }

        echo sprintf("√ %s files updated.\n", $successfulResults);
        echo sprintf("√ %s files failed.\n", $allResults - $successfulResults);
    }

    /**
     * @return Builder
     */
    protected function getQuery(): Builder
    {
        return File::query()->where(function (Builder $builder) {
            if (!$this->option('refresh')) {
                $builder->whereNull([
                    'width', 'height', 'size', 'ext',
                ], 'or');
            }
        });
    }
}
