<?php

namespace App\Services\FileService\Configs;

use App\Services\FileService\AbstractFileConfig;
use App\Services\FileService\Models\FileObject;

class FileConfig extends AbstractFileConfig
{
    public const GROUP_NAME = 'file';

    /**
     * @var array
     */
    protected array $extensions = [
        'pdf',
        'doc',
        'docx',
        'zip',
        'xls',
        'xlsx',
        'stl',
        'ply',
    ];

    /**
     * @var array
     */
    protected array $mime_types = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/zip',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'model/stl',
        'application/vnd.ms-pki.stl',
        'application/stl',
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
