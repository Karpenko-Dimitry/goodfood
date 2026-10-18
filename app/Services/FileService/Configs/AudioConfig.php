<?php

namespace App\Services\FileService\Configs;

use App\Services\FileService\AbstractFileConfig;
use App\Services\FileService\Models\FileObject;

class AudioConfig extends AbstractFileConfig
{
    public const GROUP_NAME = 'audio';

    /**
     * @var array
     */
    protected array $extensions = [
        'mp3',
        'wav',
        'webm',
    ];

    /**
     * @var array
     */
    protected array $mime_types = [
        'audio/mpeg',
        'audio/wav',
        'audio/x-wav',
        'audio/webm',
        'audio/webm;codecs=opus',
        'video/webm', //because mime_content_type() defines audio/webm as video/webm (?)
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
