<?php

namespace App\Services\FileService\Jobs;

use App\Services\FileService\AbstractFileConfig;
use App\Services\FileService\FileService;
use App\Services\FileService\FileStream;
use App\Services\FileService\Models\File;
use App\Services\FileService\Models\FileObject;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MakeFilesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @var int
     */
    public int $timeout = 900;

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
    protected ?array $fileSources;

    /**
     * @var string
     */
    protected string $tmpFilePath;

    /**
     * @var FileService
     */
    protected FileService $fileService;

    /**
     * Create a new job instance.
     *
     * MakeFilesJob constructor.
     * @param FileObject $fileObject
     * @param AbstractFileConfig $fileConfig
     * @param array $fileSources
     * @param string $tmpFilePath
     */
    public function __construct(
        FileObject $fileObject,
        AbstractFileConfig $fileConfig,
        array $fileSources,
        string $tmpFilePath
    ) {
        $this->fileObject = $fileObject;
        $this->fileConfig = $fileConfig;
        $this->fileSources = $fileSources;
        $this->tmpFilePath = $tmpFilePath;

        $this->queue = config('file.queue_name');
        $this->fileService = resolve('file_service');
    }

    /**
     * Execute the job.
     *
     * @throws Exception
     */
    public function handle(): void
    {
        $state = FileObject::STATE_UPLOADING_FAILED;

        if ($this->fileService->storageFileExists($this->tmpFilePath, $this->fileService->tmpStorage())) {
            try {
                $fileStream = new FileStream($this->tmpFilePath, $this->fileService->tmpStorage());

                $this->fileService->makeFiles(
                    $this->fileObject,
                    $this->fileConfig,
                    $this->fileSources,
                    $fileStream
                );

                $state = FileObject::STATE_UPLOADED;
            } catch (Exception $exception) {
                logger()->error($exception->getMessage());
            }

            $this->fileService->deleteTmpFile($this->tmpFilePath);
        }

        $this->fileObject->update(compact('state'));
    }

    /**
     * @param null $exception
     */
    public function fail($exception = null): void
    {
        $this->fileObject->update([
            'state' => $this->fileObject::STATE_UPLOADING_FAILED_TIMEOUT,
        ]);
    }
}
