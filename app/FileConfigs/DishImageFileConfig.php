<?php

namespace App\FileConfigs;

use App\Services\FileService\Configs\ImageConfig;
use App\Services\FileService\Exceptions\FileSourceAlreadyExistsException;
use App\Services\FileService\FileSources\FileSource;
use App\Services\FileService\FileSources\ImageSource;

/**
 * Dish photo presets (4:3, WebP). Used by Dish::imageUrl()/imageSrcset() and <x-image>.
 *  thumb — meal plan list (80px), sm/md/lg — srcset for cards and the recipe page.
 */
class DishImageFileConfig extends ImageConfig
{
    /**
     * @var string|null
     */
    protected ?string $name = 'dish_image';

    /**
     * @throws FileSourceAlreadyExistsException
     */
    public function __construct()
    {
        $this->use_queue = config('file.use_queue');
        $this->save_dominant_color = config('file.calc_dominant_color');

        $this->addFileSource(new ImageSource('thumb', 160, 160, false, 80, 'webp'));
        $this->addFileSource(new ImageSource('sm', 480, 360, false, 78, 'webp'));
        $this->addFileSource(new ImageSource('md', 960, 720, false, 78, 'webp'));
        $this->addFileSource(new ImageSource('lg', 1440, 1080, false, 78, 'webp'));
        $this->addFileSource(FileSource::createOriginal('original'));
    }
}
