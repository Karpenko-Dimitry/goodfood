<?php

namespace App\Services\FileService\Configs;

use App\Services\FileService\AbstractFileConfig;
use App\Services\FileService\Models\File;
use App\Services\FileService\Models\FileObject;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;

class ImageConfig extends AbstractFileConfig
{
    public const GROUP_NAME = 'image';

    protected array $extensions = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'bmp',
        'jfif',
        'heif',
        'heic',
    ];

    /**
     * @var array
     */
    protected array $mime_types = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/bmp',
        'image/heif',
        'image/heic',
        'image/heif-sequence',
        'image/heic-sequence',
    ];

    /**
     * @var int
     */
    protected int $preview_aspect_ratio = 1;

    /**
     * @noinspection PhpUnused
     * @return int|null
     */
    public function getPreviewAspectRatio(): ?int
    {
        return $this->preview_aspect_ratio ?? null;
    }

    /**
     * @param FileObject $fileObject
     * @param bool $fromQueue
     * @return void
     */
    public function onFilesUpdated(FileObject $fileObject, bool $fromQueue = false): void
    {
        if ($fromQueue && $this->save_dominant_color && $fileObject->files()->count() > 0) {
            /** @var File $file */
            $file = $fileObject->findFile($this->getRegenerationTypeName());
            $dominant_color = $this->getDominantColor($file->getStream());

            $fileObject->update(compact('dominant_color'));
        }
    }

    /**
     * @param string $sourcePath
     * @return mixed
     */
    public function getDominantColor(string $sourcePath)
    {
        $manager = new ImageManager(new Driver());
        $image = clone $manager->read(file_get_contents($sourcePath));

        // Reduce to single color and then sample
        $color = $image->reduceColors(1)->pickColor(0, 0, 'hex');
        unset($image);

        return $color;
    }
}
