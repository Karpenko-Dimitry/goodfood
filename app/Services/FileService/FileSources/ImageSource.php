<?php

namespace App\Services\FileService\FileSources;

use App\Services\FileService\AbstractFileSource;
use App\Services\FileService\FileStream;
use App\Services\FileService\Models\File;
use App\Services\FileService\Models\FileObject;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\FilesystemAdapter;
use Intervention\Image\ImageManager;

class ImageSource extends AbstractFileSource
{
    /**
     * Image width.
     *
     * @var int
     */
    public int $width = 1000;

    /**
     * Image height.
     *
     * @var int|null
     */
    public ?int $height = null;

    /**
     * Keep a file aspect ratio.
     *
     * @var bool
     */
    public bool $preserve_aspect_ratio = true;

    /**
     * Set null to preserve the original format.
     *
     * @var string|null
     */
    public ?string $format = 'jpg';

    /**
     * @var bool
     */
    public bool $allow_transparency = false;

    /**
     * @var string
     */
    public string $transparent_bg_color = '#ffffff';

    /**
     * @var bool
     */
    protected bool $use_original = false;

    /**
     * ImageSource constructor.
     *
     * @param string $name
     * @param int $width
     * @param int $height
     * @param bool $preserveAspectRatio
     * @param int $quality
     * @param string|null $format
     */
    public function __construct(
        string $name,
        int $width = 1000,
        int $height = 1000,
        bool $preserveAspectRatio = true,
        int $quality = 75,
        ?string $format = 'jpg'
    ) {
        $this->width = $width;
        $this->height = $height;
        $this->preserve_aspect_ratio = $preserveAspectRatio;

        parent::__construct($name, $format, $quality);
    }

    /**
     * @noinspection PhpUnused
     *
     * @param bool $allow
     * @return $this
     */
    public function setTransparency(bool $allow = true): self
    {
        $this->allow_transparency = $allow;

        return $this;
    }

    /**
     * @noinspection PhpUnused
     *
     * @param string $hex_color
     * @return $this
     */
    public function setTransparencyBgColor(string $hex_color = '#ffffff'): self
    {
        $this->transparent_bg_color = $hex_color;

        return $this;
    }

    /**
     * @param string $format
     * @return $this
     */
    public function setFormat(string $format = 'jpg'): self
    {
        $this->format = $format;

        return $this;
    }

    /**
     * @param bool $preserve_aspect_ratio
     * @return $this
     *
     * @noinspection PhpUnused
     */
    public function setPreserveAspectRatio(bool $preserve_aspect_ratio = true): self
    {
        $this->preserve_aspect_ratio = $preserve_aspect_ratio;

        return $this;
    }

    /**
     * Use the original image.
     *
     * @param string $name
     * @return ImageSource
     */
    public static function createOriginal(string $name): self
    {
        return (new self($name))->setUseOriginal(true);
    }

    /**
     * @param FileStream $fileStream
     * @param FilesystemAdapter $storage
     * @param string $storagePath
     * @param FileObject $fileObject
     *
     * @throws Exception
     *
     * @return Model|mixed
     */
    public function makeFile(
        FileStream $fileStream,
        FilesystemAdapter $storage,
        string $storagePath,
        FileObject $fileObject
    ): mixed {
        $visibility = 'public';

        $image = ImageManager::gd()->read($fileStream->getStream());
        $ext = $fileObject->ext;
        $height = $image->height();
        $width = $image->width();

        if ($this->use_original) {
            $outPath = $this->makeUniquePath($storage, $storagePath, $fileObject->ext);
            $storage->writeStream($outPath, $fileStream->getStream(), compact('visibility'));
        } else {
            $format = $this->format ?: $fileObject->ext;
            $outPath = $this->makeUniquePath($storage, $storagePath, $format);

            if ($this->preserve_aspect_ratio) {
                $image = $image->resizeDown($this->width, $this->height);
            } else {
                $image = $image->cover($this->width, $this->height);
            }

            if ($format !== 'png' || !$this->allow_transparency) {
                $image = ImageManager::gd()->create(
                    $image->width(),
                    $image->height(),
                )
                    ->fill($this->transparent_bg_color)
                    ->place($image);
            }

            $storage->put(
                $outPath,
                $image->encodeByMediaType("image/$format", quality: $this->quality)->toFilePointer(),
                $visibility
            );

            $ext = $format;
            $height = $image->height();
            $width = $image->width();
        }

        return tap($fileObject->files()->firstOrCreate([
            'key' => $this->name,
        ]))->update([
            'path' => $outPath,
            'ext' => $ext ?? null,
            'width' => $width ?? null,
            'height' => $height ?? null,
            'size' => $storage->size($outPath) ?: null,
        ]);
    }

    /**
     * @param FilesystemAdapter $storage
     * @param string $storagePath
     * @param File $file
     * @param FileObject $fileObject
     *
     * @throws Exception
     *
     * @return File
     */
    public function copyFile(
        FilesystemAdapter $storage,
        string $storagePath,
        File $file,
        FileObject $fileObject
    ): File {
        $format = $this->format ?: $fileObject->ext;
        $outPath = $this->makeUniquePath($storage, $storagePath, $format);
        $storage->copy($file->path, $outPath);

        return $fileObject->files()->create([
            'key' => $file->key,
            'path' => $outPath,
        ]);
    }

    /**
     * @noinspection PhpUnused
     *
     * @return bool
     */
    public function getUseOriginal(): bool
    {
        return $this->use_original;
    }

    /**
     * @param bool $use_original
     * @return $this
     */
    public function setUseOriginal(bool $use_original): self
    {
        $this->use_original = $use_original;

        return $this;
    }
}
