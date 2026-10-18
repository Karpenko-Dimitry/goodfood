<?php

namespace App\Services\FileService\DefaultConfigs;

use App\Services\FileService\Configs\ImageConfig;
use App\Services\FileService\Exceptions\FileSourceAlreadyExistsException;
use App\Services\FileService\FileSources\ImageSource;

class ModelImageConfig extends ImageConfig
{
    protected ?string $name = 'model_image';
    protected string $type = self::TYPE_MULTIPLE;
    protected bool $should_defer_uploading = true;

    /**
     * ModelImageConfig constructor.
     * @throws FileSourceAlreadyExistsException
     */
    public function __construct()
    {
        $this->use_queue = config('file.use_queue');
        $this->save_dominant_color = config('file.calc_dominant_color');

        $this->addFileSource(new ImageSource('thumbnail', 200, 200, false, 90));
        $this->addFileSource(ImageSource::createOriginal('original'));
    }
}
