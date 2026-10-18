<?php

namespace App\Services\FileService\Console\Commands;

use App\Services\FileService\FileService;
use Exception;
use Illuminate\Console\Command;

class FilesCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'files:cleanup
                            {minutes_to_expire? : How old in minutes should me files to be considered expired.}
                            {--force : Do not ask for confirmation before deleting.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean files';

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle(): void
    {
        $minutes = null;

        if ($this->hasArgument('minutes_to_expire')) {
            if (!is_numeric($this->argument('minutes_to_expire'))) {
                $this->error("Invalid argument `minutes_to_expire`.\n");
                exit();
            }

            $minutes = (int) $this->argument('minutes_to_expire');
        }

        $this->fileObjectsWithoutFileable();
        $this->expiredFileObjects($minutes);
        $this->unusedFiles();
    }

    /**
     * @throws Exception
     */
    public function fileObjectsWithoutFileable(): void
    {
        /** @var FileService $fileService */
        $fileService = resolve('file_service');
        $countFileObjects = count($fileService->getFileObjectsWithoutFileableList());

        if ($countFileObjects > 0) {
            echo sprintf("%s files without fileable where found.\n", $countFileObjects);

            if ($this->option('force') ||
                $this->confirm('Would you like to delete them?')) {
                echo sprintf(
                    "√ %s files deleted.\n",
                    $fileService->clearFileObjectsWithoutFileable()
                );
            } else {
                echo "√ Skipped.\n";
            }
        } else {
            echo "√ No files without fileable found.\n";
        }
    }

    /**
     * @param int $minutes
     * @throws Exception
     */
    public function expiredFileObjects(int $minutes): void
    {
        /** @var FileService $fileService */
        $fileService = resolve('file_service');
        $countFileObjects = count($fileService->getExpiredList($minutes ?: 5 * 60));

        if ($countFileObjects > 0) {
            echo sprintf("%s expired files found.\n", $countFileObjects);

            if ($this->option('force') ||
                $this->confirm('Would you like to delete them?')) {
                echo sprintf(
                    "√ %s files deleted.\n",
                    $fileService->clearExpiredFileObjects($minutes ?: 5 * 60)
                );
            } else {
                echo "√ Skipped.\n";
            }
        } else {
            echo "√ No expired files found.\n";
        }
    }

    /**
     * @throws Exception
     */
    public function unusedFiles(): void
    {
        /** @var FileService $fileService */
        $fileService = resolve('file_service');
        $countFiles = count($fileService->getUnusedFilesList());

        if ($countFiles > 0) {
            echo sprintf("%s unused files found.\n", $countFiles);

            if ($this->option('force') ||
                $this->confirm('Would you like to delete them?')) {
                echo sprintf("√ %s files deleted.\n", $fileService->clearStorage());
            } else {
                echo "√ Skipped.\n";
            }
        } else {
            echo "√ No unused files found.\n";
        }
    }
}
