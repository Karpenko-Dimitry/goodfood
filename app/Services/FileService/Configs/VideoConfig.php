<?php

namespace App\Services\FileService\Configs;

use App\Services\FileService\AbstractFileConfig;
use App\Services\FileService\Models\FileObject;

class VideoConfig extends AbstractFileConfig
{
    public const GROUP_NAME = 'video';

    /**
     * @var array
     */
    protected array $extensions = [
        'mp4',
        '3gp',
        'webm',
        'ogv',
        'avi',
        'mkv',
        'mov',
    ];

    /**
     * @var array
     */
    protected array $mime_types = [
        'video/mp4',
        'video/3gpp',
        'video/webm',
        'video/ogg',
        'video/x-msvideo',
        'video/x-matroska',
        'video/quicktime',
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
