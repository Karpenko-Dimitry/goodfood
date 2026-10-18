<?php

namespace App\Services\FileService;

use Illuminate\Filesystem\FilesystemAdapter;

class FileStream
{
    protected ?string $filePath;
    protected ?FilesystemAdapter $storage;

    /**
     * @param string|null $filePath
     * @param FilesystemAdapter|null $storage
     */
    public function __construct(?string $filePath = null, ?FilesystemAdapter $storage = null)
    {
        $this->storage = $storage;
        $this->filePath = $filePath;
    }

    /**
     * @return resource|null
     */
    public function getStream()
    {
        return $this->storage ? $this->storage->readStream($this->filePath) : fopen($this->filePath, 'r');
    }

    /**
     * @noinspection PhpUnused
     * @return string|null
     */
    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    /**
     * @return FilesystemAdapter|null
     */
    public function getStorage(): ?FilesystemAdapter
    {
        return $this->storage;
    }
}
