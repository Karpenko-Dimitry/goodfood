<?php

namespace App\Services\FileService\Console\Commands;

use App\Services\FileService\FileService;
use Exception;
use Illuminate\Console\Command;

class FileRegenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'file:regenerate
                            {file_type? : Name for the config to be regenerated or `all` to regenerate all file objects.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate file';

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle(): void
    {
        /** @var FileService $fileService */
        $fileService = resolve('file_service');
        $fileConfigs = false;
        $fileType = $this->argument('file_type');

        if (!$fileType) {
            exit("Please provider file type or type 'all'!");
        }

        if ($fileType === 'all') {
            $fileConfigs = array_values(FileService::getFileConfigs());
        } elseif ($fileConfig = FileService::getFileConfig($fileType)) {
            $fileConfigs = [$fileConfig];
        }

        if (!$fileConfigs) {
            exit(sprintf(
                "Invalid `file_type` value \"%s\" provided.\n",
                $fileType
            ));
        }

        foreach ($fileConfigs as $fileConfig) {
            echo $fileConfig->getName() . "\n";
            $fileService->regenerateFileObject($fileConfig);
        }
    }
}
