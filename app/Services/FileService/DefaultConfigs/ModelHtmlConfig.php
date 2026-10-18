<?php

namespace App\Services\FileService\DefaultConfigs;

use App\Services\FileService\Configs\HtmlConfig;
use App\Services\FileService\Exceptions\FileSourceAlreadyExistsException;
use App\Services\FileService\FileSources\HtmlSource;

class ModelHtmlConfig extends HtmlConfig
{
    protected ?string $name = 'model_html';
    protected string $type = self::TYPE_MULTIPLE;
    protected bool $should_defer_uploading = true;

    /**
     * ModelHtmlConfig constructor.
     * @throws FileSourceAlreadyExistsException
     */
    public function __construct()
    {
        $this->addFileSource(HtmlSource::createOriginal('original'));
    }
}
