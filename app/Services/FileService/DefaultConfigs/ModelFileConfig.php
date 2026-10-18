<?php

namespace App\Services\FileService\DefaultConfigs;

use App\Services\FileService\Configs\FileConfig;
use App\Services\FileService\Exceptions\FileSourceAlreadyExistsException;
use App\Services\FileService\FileSources\FileSource;

class ModelFileConfig extends FileConfig
{
    protected ?string $name = 'model_file';
    protected string $type = self::TYPE_MULTIPLE;
    protected bool $should_defer_uploading = true;

    /**
     * ModelFileConfig constructor.
     * @throws FileSourceAlreadyExistsException
     */
    public function __construct()
    {
        $this->addFileSource(FileSource::createOriginal('original'));
    }
}
