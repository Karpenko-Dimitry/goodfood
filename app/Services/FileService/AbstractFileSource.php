<?php

namespace App\Services\FileService;

use App\Services\FileService\Models\File;
use App\Services\FileService\Models\FileObject;
use Exception;
use Illuminate\Filesystem\FilesystemAdapter;
use Str;

abstract class AbstractFileSource
{
    /**
     * File name.
     * @var string|null
     */
    public ?string $name = null;

    /**
     * File final quality.
     * @var int
     */
    public int $quality = 75;

    /**
     * File format.
     * @var string|null
     */
    public ?string $format = null;

    /**
     * @var null|string
     */
    public ?string $queue = null;

    /**
     * FileType constructor.
     * @param string $name
     * @param string|null $format
     * @param string $quality
     */
    public function __construct(
        string $name,
        ?string $format,
        string $quality
    ) {
        $this->name = $name;
        $this->format = $format;
        $this->quality = $quality;
    }

    /**
     * @param FilesystemAdapter $storage
     * @param string $storagePath
     * @param string|null $extension
     * @throws Exception
     * @return string
     */
    public function makeUniquePath(
        FilesystemAdapter $storage,
        string $storagePath,
        ?string $extension = null
    ): string {
        $extension = $extension ?: $this->format;

        return Str::start(sprintf(
            '%s/%s.%s',
            $storagePath,
            $this->makeUniqueFileNme($storage, $storagePath, $extension),
            $extension
        ), '/');
    }

    /**
     * @param FileStream $fileStream
     * @param FilesystemAdapter $storage
     * @param string $storagePath
     * @param FileObject $fileObject
     * @return mixed
     */
    abstract public function makeFile(
        FileStream $fileStream,
        FilesystemAdapter $storage,
        string $storagePath,
        FileObject $fileObject
    ): mixed;

    /**
     * @param FilesystemAdapter $storage
     * @param string $storagePath
     * @param File $file
     * @param FileObject $fileObject
     * @return mixed
     */
    abstract public function copyFile(
        FilesystemAdapter $storage,
        string $storagePath,
        File $file,
        FileObject $fileObject
    ): mixed;

    /**
     * Returns a unique string that is free to be used as file name.
     *
     * @param FilesystemAdapter $storage
     * @param string $path
     * @param string $ext
     * @throws Exception
     * @return string
     */
    protected function makeUniqueFileNme(
        FilesystemAdapter $storage,
        string $path,
        string $ext
    ): string {
        do {
            $name = bin2hex(random_bytes(64 / 2));
        } while ($storage->exists(sprintf('%s/%s.%s', $path, $name, $ext)));

        return $name;
    }
}
