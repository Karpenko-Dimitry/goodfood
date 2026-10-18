<?php

namespace App\Services\FileService;

use App\Services\FileService\Exceptions\FileConfigAlreadyRegisteredException;
use App\Services\FileService\Jobs\MakeFilesJob;
use App\Services\FileService\Jobs\RegenerateFileJob;
use App\Services\FileService\Models\File;
use App\Services\FileService\Models\FileObject;
use Exception;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Carbon;
use League\Flysystem\FilesystemException;
use RuntimeException;
use Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipStream\ZipStream;

class FileService
{
    /**
     * FileObject model.
     * @var FileObject $model
     */
    protected FileObject $model;

    /**
     * The Default Filesystem driver to use for storage
     * May be overwritten in a config file.
     * @var string $storageDriver
     */
    protected string $storageDriver;

    /**
     * The Default storage path
     * May be overwritten in a config file.
     * @var string $storagePath
     */
    protected string $storagePath;

    /**
     * Temporary Filesystem driver.
     *
     * @var string $storageDriver
     */
    protected string $tmpStorageDriver;

    /**
     * Temporary storage path.
     *
     * @var string $tmpStoragePath
     */
    protected string $tmpStoragePath;

    /**
     * @var bool $deferUploading
     */
    protected mixed $deferUploading;

    /**
     * @var array|AbstractFileConfig[]
     */
    protected static array $fileConfigs = [];

    /**
     * FileService constructor.
     */
    public function __construct()
    {
        $this->model = new FileObject();
        $this->storagePath = Str::start(config('file.storage_path'), '/');
        $this->storageDriver = config('file.filesystem_driver', 'local');
        $this->tmpStoragePath = Str::start(config('file.tmp_storage_path'), '/');
        $this->tmpStorageDriver = config('file.tmp_filesystem_disk', 'temp');
        $this->deferUploading = config('file.defer_uploading');
    }

    /**
     * @param array $configs
     * @param bool $append
     * @throws FileConfigAlreadyRegisteredException
     * @return AbstractFileConfig[]|array
     */
    public static function setFileConfigs(array $configs = [], bool $append = true): array
    {
        if (!$append) {
            self::$fileConfigs = [];
        }

        /** @var AbstractFileConfig $config */
        foreach ($configs as $config) {
            self::addFileConfig($config);
        }

        return self::getFileConfigs();
    }

    /**
     * @return AbstractFileConfig[]|array
     */
    public static function getFileConfigs(): array
    {
        return self::$fileConfigs;
    }

    /**
     * @param AbstractFileConfig $fileConfig
     * @throws FileConfigAlreadyRegisteredException
     * @return AbstractFileConfig
     */
    public static function addFileConfig(AbstractFileConfig $fileConfig): AbstractFileConfig
    {
        if (isset(self::$fileConfigs[$fileConfig->getName()])) {
            throw new FileConfigAlreadyRegisteredException(sprintf(
                'File config %s already registered',
                $fileConfig->getName()
            ));
        }

        return self::$fileConfigs[$fileConfig->getName()] = $fileConfig;
    }

    /**
     * @param string $name
     * @return AbstractFileConfig|null
     */
    public static function getFileConfig(string $name): ?AbstractFileConfig
    {
        return self::$fileConfigs[$name] ?? null;
    }

    /**
     * @noinspection PhpUnused
     * @param string|null $name
     * @param string|null $ext
     * @return AbstractFileConfig|null
     */
    public static function findFileConfig(?string $name, ?string $ext): ?AbstractFileConfig
    {
        return self::getFileConfig($name ?? self::findFileConfigName($ext));
    }

    /**
     * @param string|null $ext
     * @return string
     */
    public static function findFileConfigName(?string $ext = null): string
    {
        $configs = get_objects_in_directory(
            app_path('Services/FileService/Configs'),
            'App\\Services\\FileService\\Configs\\'
        );

        /** @var AbstractFileConfig $config */
        foreach ($configs as $config) {
            if (in_array(Str::lower($ext), $config->getExtensions(), true)) {
                return AbstractFileConfig::DEFAULT_CONFIG_PREFIX . $config::GROUP_NAME;
            }
        }

        return AbstractFileConfig::getDefaultConfigName();
    }

    /**
     * @param $driver
     * @return void
     */
    public function setStorageDriver($driver): void
    {
        $this->storageDriver = $driver;
    }

    /**
     * @return string
     */
    public function getStorageDriver(): string
    {
        return $this->storageDriver;
    }

    /**
     * @param FileObject $fileObject
     * @param $type
     * @return Model|HasMany|null
     * @noinspection PhpUnused
     */
    public function getFileModel(FileObject $fileObject, $type): Model|HasMany|null
    {
        return $fileObject->files()->where('type', $type)->first();
    }

    /**
     * Remove expired and missing from db files.
     *
     * @throws Exception
     */
    public function clear(): void
    {
        $this->clearFileObjectsWithoutFileable();
        $this->clearExpiredFileObjects();
        $this->clearStorage();
    }

    /**
     * Delete all file objects with missing fileable.
     *
     * @throws Exception
     * @return int
     */
    public function clearFileObjectsWithoutFileable(): int
    {
        $fileObjects = $this->getFileObjectsWithoutFileableList();

        foreach ($fileObjects as $fileObject) {
            $this->unlink($fileObject);
        }

        return $fileObjects->count();
    }

    /**
     * Get all file objects with missing fileable.
     *
     * @return FileObject[]|Collection
     */
    public function getFileObjectsWithoutFileableList(): Collection|array
    {
        return $this->model->newQuery()->with('fileable')->whereNotNull(
            'fileable_id'
        )->whereNotNull('fileable_type')->get()->filter(function (FileObject $fileObject) {
            return is_null($fileObject->fileable);
        });
    }

    /**
     * Clear file objects that are created but not assigned to any resource.
     *
     * @param float|int $minutes_to_expire
     * @throws Exception
     * @return int
     */
    public function clearExpiredFileObjects(float|int $minutes_to_expire = 5 * 60): int
    {
        $expiredFileObjects = $this->getExpiredList($minutes_to_expire);

        foreach ($expiredFileObjects as $fileObject) {
            $this->unlink($fileObject);
        }

        return count($expiredFileObjects);
    }

    /**
     * Returns list of all files uploaded to storage but not assigned to any entity.
     *
     * @param float|int $minutes_to_expire
     * @return Collection
     */
    public function getExpiredList(float|int $minutes_to_expire = 5 * 60): Collection
    {
        return $this->model->newQuery()->where(function (Builder $query) {
            $query->whereNull('fileable_type');
            $query->orWhereNull('fileable_id');
        })->where('created_at', '<', Carbon::now()->subMinutes($minutes_to_expire))
            ->get();
    }

    /**
     * Delete all files that exist on storage but are not listed in db.
     *
     * @return int count files deleted
     */
    public function clearStorage(): int
    {
        $unusedFiles = $this->getUnusedFilesList();
        $storage = $this->storage();

        foreach ($unusedFiles as $unusedFile) {
            $storage->delete($unusedFile);
        }

        return count($unusedFiles);
    }

    /**
     * Make list files which exists on storage but are not listed in db.
     *
     * @return array
     */
    public function getUnusedFilesList(): array
    {
        $storage = $this->storage();
        $dbFiles = File::query()->pluck('path');

        return array_filter($storage->allFiles(
            $this->storagePath
        ), static function ($file) use ($dbFiles) {
            return $dbFiles->search(Str::start($file, '/')) === false;
        });
    }

    /**
     * Delete file objects from db and storage.
     *
     * @param FileObject $fileObject
     * @throws Exception
     * @return bool|null
     */
    public function unlink(FileObject $fileObject): ?bool
    {
        foreach ($fileObject->files as $file) {
            $file->unlink();
            $file->delete();
        }

        return $fileObject->delete();
    }

    /**
     * @param string $filePath
     * @param string $fileName
     * @param string $type
     * @param array|null $syncFiles
     * @param string|null $mimeType
     * @param int|null $size
     * @param bool|null $should_defer_uploading
     * @throws Exception
     * @return FileObject
     */
    public function uploadSingle(
        string $filePath,
        string $fileName,
        string $type,
        ?array $syncFiles = null,
        ?string $mimeType = null,
        ?int $size = null,
        ?bool $should_defer_uploading = null
    ): FileObject {
        return $this->makeFileObject(
            new FileStream($filePath),
            pathinfo($fileName, PATHINFO_FILENAME),
            pathinfo($fileName, PATHINFO_EXTENSION),
            $type,
            $syncFiles,
            $mimeType,
            $size,
            $should_defer_uploading
        );
    }

    /**
     * @throws Exception
     * @return string
     */
    public function makeUniqueUuid(): string
    {
        do {
            $uuid = Str::uuid();
        } while ($this->model->newQuery()->where(compact('uuid'))->exists());

        return $uuid;
    }

    /**
     * @param FileObject $fileObject
     * @param AbstractFileConfig $fileConfig
     * @param AbstractFileSource[] $fileSources
     * @param FileStream $fileStream
     * @param array|null $syncFiles
     * @param bool $fromQueue
     * @return FileObject
     */
    public function makeFiles(
        FileObject $fileObject,
        AbstractFileConfig $fileConfig,
        array $fileSources,
        FileStream $fileStream,
        ?array $syncFiles = null,
        bool $fromQueue = false
    ): FileObject {
        $useQueue = !$fromQueue && $fileConfig->useQueue();

        if ($useQueue) {
            /** @var AbstractFileSource[] $fileSources */
            $fileSources = array_values(array_filter($fileSources, static function (
                AbstractFileSource $fileSource
            ) use ($fileConfig, $syncFiles) {
                return in_array(
                    $fileSource->name,
                    is_null($syncFiles)
                        ? $fileConfig->getSyncFiles()
                        : array_merge(
                            [$fileConfig->getRegenerationTypeName()],
                            $syncFiles
                        ),
                    true
                );
            }));
        }

        foreach ($fileSources as $fileSource) {
            $fileSource->makeFile($fileStream, $this->storage(), $this->storagePath, $fileObject);
        }

        if ($useQueue) {
            RegenerateFileJob::dispatch($fileConfig, $fileObject, $fileSources);
        }

        $fileConfig->onFilesUpdated($fileObject, $fromQueue);

        return $fileObject;
    }

    /**
     * @param FileStream $fileStream
     * @param string $ext
     * @throws Exception
     * @return string
     */
    public function storeTempFile(FileStream $fileStream, string $ext): string
    {
        $tmpFilePath = $this->makeUniquePath($ext, $this->tmpStoragePath, $this->tmpStorage());
        $this->tmpStorage()->writeStream($tmpFilePath, $fileStream->getStream());

        return $tmpFilePath;
    }

    /**
     * @param string $ext
     * @param string|null $storagePath
     * @param Filesystem|null $storage
     * @return string
     */
    public function makeUniquePath(
        string $ext,
        ?string $storagePath = null,
        ?Filesystem $storage = null
    ): string {
        $storage = $storage ?: $this->storage();
        $storagePath = $storagePath ?: $this->storagePath;
        $fileName = $this->makeUniqueFileName($ext, $storage, $storagePath);

        return Str::start(sprintf('%s/%s.%s', $storagePath, $fileName, $ext), '/');
    }

    /**
     * Returns a unique string that is free to be used as file name.
     *
     * @param Filesystem $storage
     * @param string $ext
     * @param string|null $storagePath
     * @return string
     */
    public function makeUniqueFileName(
        string $ext,
        Filesystem $storage,
        ?string $storagePath = null
    ): string {
        do {
            $name = bin2hex(openssl_random_pseudo_bytes(64 / 2));
            $path = sprintf('%s/%s.%s', $storagePath, $name, $ext);
        } while ($storage->exists($path));

        return $name;
    }

    /**
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function regenerateAllFileObjects(): void
    {
        foreach (self::getFileConfigs() as $fileConfig) {
            if ($fileConfig->getFileSources()[
                $fileConfig->getRegenerationTypeName()] ?? false) {
                $this->regenerateFileObject($fileConfig);
            }
        }
    }

    /**
     * @param AbstractFileConfig $fileConfig
     * @param FileObject|null $fileObject
     * @param bool $fromQueue
     * @param array $keepFileSources
     * @throws Exception
     */
    public function regenerateFileObject(
        AbstractFileConfig $fileConfig,
        ?FileObject $fileObject = null,
        bool $fromQueue = false,
        array $keepFileSources = []
    ): void {
        $sourceFileName = $fileConfig->getRegenerationTypeName();

        /** @var Builder $fileObjects */
        $fileObjects = $this->model->newQuery()->where([
            'type' => $fileConfig->getName(),
        ]);

        if ($fileObject) {
            $fileObjects->where('id', $fileObject->id);
        }

        $keepFileSourceKeys = array_map(static function (AbstractFileSource $fileSource) {
            return $fileSource->name;
        }, $keepFileSources);

        $keepFileSourceKeys[] = $sourceFileName;

        $newFileSources = array_filter($fileConfig->getFileSources(), static function (
            AbstractFileSource $fileSource
        ) use ($keepFileSourceKeys) {
            return !in_array($fileSource->name, $keepFileSourceKeys, true);
        });

        $newFileSourceKeys = array_map(static function (AbstractFileSource $fileSource) {
            return $fileSource->name;
        }, $newFileSources);

        /** @var FileObject $fileObjectModel */
        foreach ($fileObjects->get() as $fileObjectModel) {
            /** @var File $file */
            $file = $fileObjectModel->findFile($sourceFileName);

            if (!$file) {
                throw new RuntimeException(sprintf(implode([
                    "Could not regenerate files for file object \"%s\".\n",
                    "Source file \"%s\" is missing.\n",
                ]), $fileObjectModel->id, $sourceFileName));
            }

            /** @var File[] $files */
            $files = $fileObjectModel->files()->whereIn('key', $newFileSourceKeys)->get();

            foreach ($files as $fileModel) {
                $fileModel->unlink();
                $fileModel->delete();
            }

            $fileStream = new FileStream($file->path, $this->storage());
            $this->makeFiles(
                $fileObjectModel,
                $fileConfig,
                $newFileSources,
                $fileStream,
                null,
                $fromQueue
            );
        }
    }

    /**
     * @noinspection PhpUnused
     * @param FileObject $fileObject
     * @param string|null $type
     * @param bool $forceRegenerate
     * @throws Exception
     * @return Builder|FileObject|Model
     */
    public function cloneFileObject(
        FileObject $fileObject,
        ?string $type = null,
        bool $forceRegenerate = false
    ): Model|Builder|FileObject {
        $type = $type ?? $fileObject->type;
        $copyFiles = $type === $fileObject->type;

        if (!$forceRegenerate && $copyFiles) {
            return $this->cloneFileObjectCopy($fileObject);
        }

        return $this->cloneFileObjectGenerate($fileObject, $type);
    }

    /**
     * @param string|null $uuid
     * @return FileObject
     * @noinspection PhpUnused
     */
    public function findByUid(?string $uuid = null): FileObject
    {
        return $this->model->newQuery()->where('uuid', $uuid)->first();
    }

    /**
     * @return Filesystem|FilesystemAdapter
     */
    public function tmpStorage(): Filesystem|FilesystemAdapter
    {
        return resolve('filesystem')->disk($this->tmpStorageDriver);
    }

    /**
     * @param string $path
     * @return string
     */
    public function urlPublic(string $path): string
    {
        return $this->storage()->url(ltrim($path, '/'));
    }

    /**
     * @param string $path
     * @return string
     */
    public function path(string $path): string
    {
        return $this->storage()->path($path);
    }

    /**
     * @param string $path
     * @param Filesystem|null $storage
     * @return string|null
     */
    public function getContent(string $path, ?Filesystem $storage = null): ?string
    {
        return $this->storageFileExists($path, $storage) ?
            ($storage ?: $this->storage())->get($path) : null;
    }

    /**
     * @param string $path
     * @return string|null
     * @noinspection PhpUnused
     */
    public function getTmpContent(string $path): ?string
    {
        return $this->storageTmpFileExists($path) ? $this->tmpStorage()->get($path) : null;
    }

    /**
     * @param string $path
     * @param Filesystem|null $storage
     * @return resource|null
     */
    public function getStream(string $path, ?Filesystem $storage = null)
    {
        return $this->storageFileExists($path, $storage) ?
            ($storage ?: $this->storage())->readStream($path) : null;
    }

    /**
     * @param string $path
     * @return resource|null
     * @noinspection PhpUnused
     */
    public function getTmpStream(string $path)
    {
        return $this->storageTmpFileExists($path) ? $this->tmpStorage()->readStream($path) : null;
    }

    /**
     * @param string $path
     * @param Filesystem|null $storage
     * @return bool
     */
    public function storageFileExists(string $path, ?Filesystem $storage = null): bool
    {
        return ($storage ?: $this->storage())->exists($path);
    }

    /**
     * @param string $path
     * @return bool
     */
    public function storageTmpFileExists(string $path): bool
    {
        return $this->tmpStorage()->exists($path);
    }

    /**
     * @param string $path
     * @param Filesystem|null $storage
     * @return bool
     */
    public function deleteFile(string $path, ?Filesystem $storage = null): bool
    {
        return ($storage ?: $this->storage())->delete($path);
    }

    /**
     * @param string $path
     * @return bool
     */
    public function deleteTmpFile(string $path): bool
    {
        return $this->deleteFile($path, $this->tmpStorage());
    }

    /**
     * @param FileObject $fileObject
     * @param string|null $filename
     * @param bool $asStream
     * @throws FilesystemException
     * @return StreamedResponse
     */
    public function download(
        FileObject $fileObject,
        ?string $filename = null,
        bool $asStream = false
    ): StreamedResponse {
        set_time_limit(0);

        $fs = $this->storage()->getDriver();
        $filePath = $fileObject->file_original->path;
        $safeFilename = $filename ?? $fileObject->original_name;
        $safeFilename = preg_replace('/[^\w\.-]/', '_', $safeFilename);

        $mimeType = $fs->mimeType($filePath);
        $stream = $fs->readStream($filePath);

        return response()->stream(
            static function () use ($stream) {
                try {
                    fpassthru($stream);
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }
            },
            200,
            [
                'Content-Type' => $mimeType,
                'Content-Disposition' => ($asStream ? 'inline;' : 'attachment;') . ' filename="' .
                    $safeFilename . '.' . $fileObject->ext . '"',
                'Content-Length' => $fileObject->file_original->getSize(),
                'Cache-Control' => 'must-revalidate, no-cache, no-store, private',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]
        );
    }

    /**
     * @noinspection PhpUnused
     * @param $fileObjects
     * @param string $zipName
     * @param callable|null $getFilesName
     * @return StreamedResponse
     */
    public function downloadZip($fileObjects, string $zipName = 'attachments', ?callable $getFilesName = null): StreamedResponse
    {
        return response()->streamDownload(function () use ($fileObjects, $getFilesName) {
            /** @var FileService $fileService */
            $fileService = resolve('file_service');
            $fileService->setStorageDriver(config('file.filesystem_driver'));

            $zip = new ZipStream();

            /** @var FileObject $fileObject */
            foreach ($fileObjects as $fileObject) {
                try {
                    $name = is_callable($getFilesName)
                        ? call_user_func($getFilesName, $fileObject)
                        : $fileObject->original_name_with_ext;
                    $file = $fileService->getStream($fileObject->file_original->path);

                    $zip->addFileFromStream($name, $file);
                } catch (Exception $e) {
                    logger()->error("unable to read the file at storage path: {$fileObject->file_original->path}
                    and output to zip stream. Exception is " . $e->getMessage());
                }

            }

            $zip->finish();
        }, $zipName . '.zip');
    }

    /**
     * @param $path
     * @return int
     */
    public function getFileSize($path): int
    {
        return $this->storage()->size($path);
    }

    /**
     * @throws FilesystemException
     */
    public function getMimeType($path): string
    {
        $fs = $this->storage()->getDriver();

        return $fs->mimeType($path);
    }

    /**
     * @param string $original_name
     * @param string $ext
     * @param string $type
     * @param bool $deferUploading
     * @param string|null $mime_type
     * @param int|null $size
     * @throws Exception
     * @return Model|Builder|FileObject
     */
    protected function makeFileObjectModel(
        string $original_name,
        string $ext,
        string $type,
        bool $deferUploading = false,
        ?string $mime_type = null,
        ?int $size = null
    ): Model|Builder|FileObject {
        $owner = auth()->user();

        if (!$fileObject = $this->model->newQuery()->create(array_merge(compact(
            'original_name',
            'type',
            'ext',
            'size',
            'mime_type'
        ), [
            'uuid' => $this->makeUniqueUuid(),
            'ownerable_id' => $owner->id ?? null,
            'ownerable_type' => $owner?->getMorphClass(),
            'state' => $deferUploading ? FileObject::STATE_PROCESSING : FileObject::STATE_UPLOADED,
        ]))) {
            throw new RuntimeException('Could not create file object model.');
        }

        return $fileObject;
    }

    /**
     * @param FileStream $fileStream
     * @param string $original_name
     * @param string $ext
     * @param string $type
     * @param array|null $syncFiles
     * @param string|null $mimeType
     * @param int|null $size
     * @param bool|null $should_defer_uploading
     * @throws Exception
     * @return FileObject
     */
    protected function makeFileObject(
        FileStream $fileStream,
        string $original_name,
        string $ext,
        string $type,
        ?array $syncFiles = null,
        ?string $mimeType = null,
        ?int $size = null,
        ?bool $should_defer_uploading = null
    ): FileObject {
        /** @var AbstractFileConfig $fileConfig */
        $fileConfig = self::getFileConfig($type);

        $deferUploading = !is_null($should_defer_uploading)
            ? $should_defer_uploading
            : ($this->deferUploading && $fileConfig->shouldDeferUploading());

        $fileObject = $this->makeFileObjectModel($original_name, $ext, $type, $deferUploading, $mimeType, $size);

        $fileSources = $fileConfig->getFileSources();

        if ($deferUploading) {
            $tempFilePath = $this->storeTempFile($fileStream, $fileObject->ext);
            MakeFilesJob::dispatch($fileObject, $fileConfig, $fileSources, $tempFilePath);
        } else {
            $this->makeFiles($fileObject, $fileConfig, $fileSources, $fileStream, $syncFiles);
        }

        return $fileObject;
    }

    /**
     * @param FileObject $fileObject
     * @throws Exception
     * @return FileObject
     */
    protected function cloneFileObjectCopy(FileObject $fileObject): FileObject
    {
        /** @var AbstractFileConfig $oldFileConfig */
        $oldFileConfig = self::getFileConfig($fileObject->type);

        /** @var File $fileModel */
        $fileModel = $fileObject->findFile($oldFileConfig->getRegenerationTypeName());
        $fileStream = new FileStream($fileModel->path, $this->storage());

        return $this->makeFileObject(
            fileStream: $fileStream,
            original_name: $fileObject->original_name,
            ext: $fileObject->ext,
            type: $fileObject->type,
            size: $fileObject->size
        );
    }

    /**
     * @param FileObject $fileObject
     * @param string $type
     * @throws Exception
     * @return FileObject|Builder|Model
     */
    protected function cloneFileObjectGenerate(FileObject $fileObject, string $type): Model|Builder|FileObject
    {
        /** @var AbstractFileConfig $fileConfig */
        $fileConfig = self::getFileConfig($type);
        /** @var AbstractFileSource $fileSources */
        $fileSources = $fileConfig->getFileSources();
        $newFileObject = $this->makeFileObjectModel(
            $fileObject->original_name,
            $fileObject->ext,
            $type
        );

        foreach ($fileSources as $fileSource) {
            /** @var File $fileModel */
            if ($fileModel = $fileObject->files()->where([
                'key' => $fileSource->name,
            ])->first()) {
                $fileSource->copyFile(
                    $this->storage(),
                    $this->storagePath,
                    $fileModel,
                    $newFileObject
                );
            }
        }

        return $newFileObject;
    }

    /**
     * @return Filesystem|FilesystemAdapter
     */
    private function storage(): Filesystem|FilesystemAdapter
    {
        return resolve('filesystem')->disk($this->storageDriver);
    }
}
