<?php

namespace App\FileConfigs;

use App\Services\FileService\Configs\ImageConfig;
use App\Services\FileService\Exceptions\FileSourceAlreadyExistsException;
use App\Services\FileService\FileSources\FileSource;
use App\Services\FileService\FileSources\ImageSource;

/**
 * Diet cover presets (3:2, WebP). sm/md — cards and home tabs, lg — full-width page header.
 */
class DietImageFileConfig extends ImageConfig
{
    /**
     * @var string|null
     */
    protected ?string $name = 'diet_image';

    /**
     * @throws FileSourceAlreadyExistsException
     */
    public function __construct()
    {
        $this->use_queue = config('file.use_queue');
        $this->save_dominant_color = config('file.calc_dominant_color');

        $this->addFileSource(new ImageSource('sm', 480, 320, false, 78, 'webp'));
        $this->addFileSource(new ImageSource('md', 960, 640, false, 78, 'webp'));
        $this->addFileSource(new ImageSource('lg', 1600, 1067, false, 75, 'webp'));
        $this->addFileSource(FileSource::createOriginal('original'));
    }
}
