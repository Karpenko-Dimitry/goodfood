<?php

namespace App\Services\FileService\FileSources;

use App\Services\FileService\AbstractFileSource;
use App\Services\FileService\FileStream;
use App\Services\FileService\Models\File;
use App\Services\FileService\Models\FileObject;
use Exception;
use getid3_exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\FilesystemAdapter;
use Owenoj\LaravelGetId3\GetId3;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class VideoSource extends AbstractFileSource
{
    /**
     * Set null to preserve the original format.
     * @var string|null
     */
    public ?string $format = null;

    /**
     * @var bool
     */
    protected bool $use_original = false;

    /**
     * The Default Filesystem driver to use for storage
     * May be overwritten in a config file.
     * @var string $storageDriver
     */
    protected string $storageDriver;

    /**
     * VideoSource constructor.
     * @param string $name
     * @param string|null $format
     * @param int $quality
     */
    public function __construct(
        string $name,
        ?string $format = 'mp4',
        int $quality = 100
    ) {
        $this->storageDriver = config('file.filesystem_driver', 'local');

        parent::__construct($name, $format, $quality);
    }

    /**
     * Use the original image.
     *
     * @param string $name
     * @return VideoSource
     */
    public static function createOriginal(string $name): VideoSource
    {
        return (new self($name))->setUseOriginal(true);
    }

    /**
     * @param FileStream $fileStream
     * @param FilesystemAdapter $storage
     * @param string $storagePath
     * @param FileObject $fileObject
     * @throws Exception
     * @return Model|mixed
     */
    public function makeFile(
        FileStream $fileStream,
        FilesystemAdapter $storage,
        string $storagePath,
        FileObject $fileObject
    ): mixed {
        $outPath = null;
        $visibility = 'public';

        if ($this->use_original) {
            $outPath = $this->makeUniquePath($storage, $storagePath, $fileObject->ext);
            $duration = $this->getVideoDuration($fileObject, $fileStream->getStream());
            $storage->writeStream($outPath, $fileStream->getStream(), compact('visibility'));
            $fileObject->update(compact('duration'));
            $ext = $fileObject->ext;
        } elseif ($video = $fileObject->findFile('original')) {
            $outPath = $this->makeUniquePath($storage, $storagePath, $this->format);
            $ext = $this->format;

            FFMpeg::fromFilesystem($storage)
                ->open($video->path)
                ->getFrameFromSeconds(1)
                ->export()
                ->toDisk($this->storageDriver)
                ->withVisibility('public')
                ->save($outPath);
        }

        return tap($fileObject->files()->firstOrCreate([
            'key' => $this->name,
        ]))->update([
            'path' => $outPath,
            'ext' => $ext ?? null,
            'size' => $storage->size($outPath) ?: null,
        ]);
    }

    /**
     * @param FilesystemAdapter $storage
     * @param string $storagePath
     * @param File $file
     * @param FileObject $fileObject
     * @throws Exception
     * @return File
     */
    public function copyFile(
        FilesystemAdapter $storage,
        string $storagePath,
        File $file,
        FileObject $fileObject
    ): File {
        $format = $this->format ?: $fileObject->ext;
        $outPath = $this->makeUniquePath($storage, $storagePath, $format);
        $storage->copy($file->path, $outPath);

        return $fileObject->files()->create([
            'key' => $file->key,
            'path' => $outPath,
        ]);
    }

    /**
     * @noinspection PhpUnused
     * @return bool
     */
    public function getUseOriginal(): bool
    {
        return $this->use_original;
    }

    /**
     * @param bool $use_original
     *
     * @return $this
     */
    public function setUseOriginal(bool $use_original): self
    {
        $this->use_original = $use_original;

        return $this;
    }

    /**
     * @param FileObject $fileObject
     * @param resource $fileStream
     * @throws getid3_exception
     * @return float
     */
    protected function getVideoDuration(FileObject $fileObject, $fileStream): float
    {
        return (new GetId3(
            $fileObject->original_name . '. ' . $fileObject->ext,
            fstat($fileStream)['size'],
            $fileStream
        ))->getPlaytimeSeconds();
    }
}
