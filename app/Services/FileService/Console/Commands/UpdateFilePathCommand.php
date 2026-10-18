<?php

namespace App\Services\FileService\Console\Commands;

use App\Services\FileService\Models\File;
use Exception;
use Illuminate\Console\Command;

class UpdateFilePathCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'files:update-path
                            {path : New path of files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update file path';

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle(): void
    {
        $this->line('Start updating', 'info');
        $newPath = $this->argument('path');
        $count = 0;

        File::query()->chunkById(1000, function ($files) use ($newPath, &$count) {
            /** @var File $file */
            foreach ($files as $file) {
                $file->path = $newPath . pathinfo($file->path, PATHINFO_BASENAME);
                $file->save();
            }

            $count += count($files);
            $this->line($count . ' files updated', 'info');
        });
    }
}
