<?php

namespace App\Services\FileService\Configs;

use App\Services\FileService\AbstractFileConfig;
use App\Services\FileService\Models\FileObject;

/**
 * Class HtmlConfig.
 */
class HtmlConfig extends AbstractFileConfig
{
    public const GROUP_NAME = 'html';

    /**
     * @var array
     */
    protected array $extensions = [
        'html',
    ];

    /**
     * @var array
     */
    protected array $mime_types = [
        'text/html',
    ];

    /**
     * @param FileObject $fileObject
     * @param bool $fromQueue
     * @return void
     */
    public function onFilesUpdated(FileObject $fileObject, bool $fromQueue = false): void
    {

    }
}
