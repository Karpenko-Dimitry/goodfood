<?php

namespace App\Services\FileService\Jobs;

use App\Services\FileService\AbstractFileConfig;
use App\Services\FileService\AbstractFileSource;
use App\Services\FileService\Models\File;
use App\Services\FileService\Models\FileObject;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RegenerateFileJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @var AbstractFileConfig
     */
    protected AbstractFileConfig $fileConfig;

    /**
     * @var FileObject|null
     */
    protected ?FileObject $fileObject;

    /**
     * @var File[]|null
     */
    protected ?array $keepFileSources;

    /**
     * Create a new job instance.
     *
     * RegenerateFileJob constructor.
     * @param AbstractFileConfig $fileConfig
     * @param FileObject|null $fileObject
     * @param AbstractFileSource[]|null $keepFileSources
     */
    public function __construct(
        AbstractFileConfig $fileConfig,
        ?FileObject $fileObject = null,
        array $keepFileSources = []
    ) {
        $this->fileObject = $fileObject;
        $this->queue = config('file.queue_name');

        $this->fileConfig = $fileConfig;
        $this->keepFileSources = $keepFileSources;
    }

    /**
     * Execute the job.
     *
     * @throws Exception
     */
    public function handle(): void
    {
        resolve('file_service')->regenerateFileObject(
            $this->fileConfig,
            $this->fileObject,
            true,
            $this->keepFileSources
        );
    }
}
