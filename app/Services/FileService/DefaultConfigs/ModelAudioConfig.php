<?php

namespace App\Services\FileService\DefaultConfigs;

use App\Services\FileService\Configs\AudioConfig;
use App\Services\FileService\Exceptions\FileSourceAlreadyExistsException;
use App\Services\FileService\FileSources\AudioSource;

class ModelAudioConfig extends AudioConfig
{
    protected ?string $name = 'model_audio';
    protected string $type = self::TYPE_MULTIPLE;

    protected bool $should_defer_uploading = true;

    /**
     * ModelVideoConfig constructor.
     * @throws FileSourceAlreadyExistsException
     */
    public function __construct()
    {
        $this->addFileSource(AudioSource::createOriginal('original'));
    }
}
