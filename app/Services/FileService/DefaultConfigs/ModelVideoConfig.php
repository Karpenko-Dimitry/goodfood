<?php

namespace App\Services\FileService\DefaultConfigs;

use App\Services\FileService\Configs\VideoConfig;
use App\Services\FileService\Exceptions\FileSourceAlreadyExistsException;
use App\Services\FileService\FileSources\VideoSource;

class ModelVideoConfig extends VideoConfig
{
    protected ?string $name = 'model_video';
    protected string $type = self::TYPE_MULTIPLE;

    protected bool $should_defer_uploading = true;

    /**
     * ModelVideoConfig constructor.
     * @throws FileSourceAlreadyExistsException
     */
    public function __construct()
    {
        $this->addFileSource(VideoSource::createOriginal('original'));
        //        $this->addFileSource(new VideoSource('thumbnail', 'jpg', 100));
    }
}
