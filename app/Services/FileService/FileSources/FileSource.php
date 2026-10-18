<?php

namespace App\Services\FileService\FileSources;

use App\Services\FileService\AbstractFileSource;
use App\Services\FileService\FileStream;
use App\Services\FileService\Models\File;
use App\Services\FileService\Models\FileObject;
use Exception;
use Illuminate\Filesystem\FilesystemAdapter;

class FileSource extends AbstractFileSource
{
    /**
     * @var bool
     */
    protected bool $use_original = true;

    /**
     * FileSource constructor.
     * @param string $name
     * @param string|null $format
     * @param int $quality
     */
    public function __construct(
        string $name,
        ?string $format = 'pdf',
        int $quality = 100
    ) {
        parent::__construct($name, $format, $quality);
    }

    /**
     * Use the original image.
     *
     * @param string $name
     * @return FileSource
     */
    public static function createOriginal(string $name): FileSource
    {
        return (new self($name))->setUseOriginal(true);
    }

    /**
     * @param FileStream $fileStream
     * @param FilesystemAdapter $storage
     * @param string $storagePath
     * @param FileObject $fileObject
     * @throws Exception
     * @return mixed
     */
    public function makeFile(
        FileStream $fileStream,
        FilesystemAdapter $storage,
        string $storagePath,
        FileObject $fileObject
    ): mixed {
        $visibility = 'public';
        $outPath = $this->makeUniquePath($storage, $storagePath, $fileObject->ext);
        $storage->writeStream($outPath, $fileStream->getStream(), compact('visibility'));

        return tap($fileObject->files()->firstOrCreate([
            'key' => $this->name,
        ]))->update([
            'path' => $outPath,
            'ext' => $fileObject->ext,
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
}
