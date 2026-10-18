<?php

namespace App\Services\FileService\Models;

use App\Services\FileService\FileService;
use Eloquent;
use GuzzleHttp\Psr7;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use League\Flysystem\FilesystemException;
use Psr\Http\Message\StreamInterface;

/**
 * App\Services\FileService\Models\File.
 *
 * @property int $id
 * @property int $file_object_id
 * @property string $key
 * @property string|null $path
 * @property string|null $ext
 * @property int|null $size
 * @property int|null $width
 * @property int|null $height
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read FileObject $fileObject
 * @method static Builder<static>|File newModelQuery()
 * @method static Builder<static>|File newQuery()
 * @method static Builder<static>|File query()
 * @method static Builder<static>|File whereCreatedAt($value)
 * @method static Builder<static>|File whereExt($value)
 * @method static Builder<static>|File whereFileObjectId($value)
 * @method static Builder<static>|File whereHeight($value)
 * @method static Builder<static>|File whereId($value)
 * @method static Builder<static>|File whereKey($value)
 * @method static Builder<static>|File wherePath($value)
 * @method static Builder<static>|File whereSize($value)
 * @method static Builder<static>|File whereUpdatedAt($value)
 * @method static Builder<static>|File whereWidth($value)
 * @mixin Eloquent
 */
class File extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'file_object_id', 'key', 'path', 'width', 'height', 'size', 'ext',
    ];

    /**
     * @return BelongsTo
     */
    public function fileObject(): BelongsTo
    {
        return $this->belongsTo(FileObject::class, 'file_object_id');
    }

    /**
     * @return FileService
     */
    public function service(): FileService
    {
        return resolve('file_service');
    }

    /**
     * @return bool
     */
    public function unlink(): bool
    {
        return $this->service()->deleteFile($this->path);
    }

    /**
     * @param string|null $driver
     * @return string
     */
    public function urlPublic(?string $driver = null): string
    {
        $defaultStorageDriver = $this->service()->getStorageDriver();
        $this->service()->setStorageDriver($driver ?? $defaultStorageDriver);
        $url = $this->path ? $this->service()->urlPublic($this->path) : '';
        $this->service()->setStorageDriver($defaultStorageDriver);

        return $url;
    }

    /**
     * @param string|null $driver
     * @return string
     */
    public function storagePath(?string $driver = null): string
    {
        if ($driver) {
            $this->service()->setStorageDriver($driver);
        }

        return $this->path ? $this->service()->path($this->path) : '';
    }

    /**
     * @param string|null $driver
     * @return string|null
     */
    public function getContent(?string $driver = null): ?string
    {
        if ($driver) {
            $this->service()->setStorageDriver($driver);
        }

        return $this->service()->getContent($this->path);
    }

    /**
     * @noinspection PhpUnused
     *
     * @param string|null $driver
     * @return StreamInterface
     */
    public function getContentBytes(?string $driver = null): StreamInterface
    {
        return Psr7\Utils::streamFor(base64_encode($this->getContent($driver)));
    }

    /**
     * @throws FilesystemException
     */
    public function getMimeType(?string $driver = null): ?string
    {
        if ($driver) {
            $this->service()->setStorageDriver($driver);
        }

        return $this->service()->getMimeType($this->path);
    }

    /**
     * @return resource|null
     */
    public function getStream()
    {
        return $this->service()->getStream($this->path);
    }

    /**
     * @return bool
     */
    public function fileExists(): bool
    {
        return $this->service()->storageFileExists($this->path);
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->fileExists() ? $this->service()->getFileSize($this->path) : 0;
    }
}
