<?php

namespace App\Services\FileService\Models;

use App\Services\FileService\FileService;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * App\Services\FileService\Models\FileObject.
 *
 * @property int $id
 * @property string|null $uuid
 * @property string|null $fileable_type
 * @property int|null $fileable_id
 * @property string|null $original_name
 * @property string $type
 * @property string $ext
 * @property string|null $mime_type
 * @property int|null $size
 * @property int|null $order
 * @property string $dominant_color
 * @property int|null $duration
 * @property string|null $ownerable_type
 * @property int|null $ownerable_id
 * @property string $state
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read File|null $file_original
 * @property-read Model|Eloquent|null $fileable
 * @property-read Collection<int, File> $files
 * @property-read int|null $files_count
 * @property-read mixed $group
 * @property-read string $original_name_with_ext
 * @property-read string $owner_name
 * @property-read Model|Eloquent|null $ownerable
 * @method static Builder<static>|FileObject newModelQuery()
 * @method static Builder<static>|FileObject newQuery()
 * @method static Builder<static>|FileObject query()
 * @method static Builder<static>|FileObject whereCreatedAt($value)
 * @method static Builder<static>|FileObject whereDominantColor($value)
 * @method static Builder<static>|FileObject whereDuration($value)
 * @method static Builder<static>|FileObject whereExt($value)
 * @method static Builder<static>|FileObject whereFileableId($value)
 * @method static Builder<static>|FileObject whereFileableType($value)
 * @method static Builder<static>|FileObject whereId($value)
 * @method static Builder<static>|FileObject whereMimeType($value)
 * @method static Builder<static>|FileObject whereOrder($value)
 * @method static Builder<static>|FileObject whereOriginalName($value)
 * @method static Builder<static>|FileObject whereOwnerableId($value)
 * @method static Builder<static>|FileObject whereOwnerableType($value)
 * @method static Builder<static>|FileObject whereSize($value)
 * @method static Builder<static>|FileObject whereState($value)
 * @method static Builder<static>|FileObject whereType($value)
 * @method static Builder<static>|FileObject whereUpdatedAt($value)
 * @method static Builder<static>|FileObject whereUuid($value)
 * @mixin Eloquent
 */
class FileObject extends Model
{
    public const STATE_UPLOADED = 'uploaded';
    public const STATE_PROCESSING = 'processing';
    public const STATE_UPLOADING_FAILED = 'uploading_failed';
    public const STATE_UPLOADING_FAILED_TIMEOUT = 'uploading_failed_timeout';

    /**
     * @var string[]
     */
    public static array $states = [
        self::STATE_UPLOADED,
        self::STATE_PROCESSING,
        self::STATE_UPLOADING_FAILED,
        self::STATE_UPLOADING_FAILED_TIMEOUT,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'original_name', 'fileable_id', 'fileable_type',
        'type', 'ext', 'uuid', 'dominant_color', 'order',
        'duration', 'ownerable_id', 'ownerable_type', 'state',
        'locale', 'mime_type', 'read_at', 'size',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * @var string[]
     */
    protected $appends = [
        'original_name_with_ext',
        'group',
        'owner_name',
    ];

    /**
     * @noinspection PhpUnused
     *
     * @return string
     */
    public function getOriginalNameWithExtAttribute(): string
    {
        return implode('.', [$this->original_name, $this->ext]);
    }

    /**
     * @noinspection PhpUnused
     */
    public function getGroupAttribute()
    {
        return FileService::getFileConfig($this->type)::GROUP_NAME;
    }

    /**
     * @noinspection PhpUnused
     *
     * @return string
     */
    public function getOwnerNameAttribute(): string
    {
        return $this->ownerable->name ?? 'System';
    }

    /**
     * @return FileService
     */
    public function service(): FileService
    {
        return resolve('file_service');
    }

    /**
     * @return HasMany
     */
    public function files(): HasMany
    {
        return $this->hasMany(File::class);
    }

    /**
     * @noinspection PhpUnused
     *
     * @return HasOne
     */
    public function file_original(): HasOne
    {
        return $this->hasOne(File::class)->where([
            'key' => 'original',
        ]);
    }

    /**
     * @return MorphTo
     */
    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @noinspection PhpUnused
     *
     * @return MorphTo
     */
    public function ownerable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param string $key
     * @return File|null
     */
    public function findFile(string $key): ?File
    {
        return $this->files->where('key', $key)->first();
    }

    /**
     * @param $uuid
     * @return self|Builder|Model|null
     */
    public static function findByUuid($uuid): Model|Builder|FileObject|null
    {
        return self::where(compact('uuid'))->first();
    }

    /**
     * @param string $key
     * @param string|null $driver
     * @return string|null
     */
    public function urlPublic(string $key, ?string $driver = null): ?string
    {
        /** @var File $file */
        if ($file = $this->findFile($key)) {
            return $file->urlPublic($driver);
        }

        return null;
    }

    /**
     * @param string $key
     * @param string|null $driver
     * @return string|null
     * @noinspection PhpUnused
     */
    public function getFileSize(string $key, ?string $driver = null): ?string
    {
        /** @var File $file */
        if ($file = $this->findFile($key)) {
            return $file->getSize();
        }

        return null;
    }

    /**
     * @param string $key
     * @param string|null $driver
     * @return string|null
     */
    public function storagePath(string $key, ?string $driver = null): ?string
    {
        /** @var File $file */
        if ($file = $this->findFile($key)) {
            return $file->storagePath($driver);
        }

        return null;
    }

    /**
     * @noinspection PhpUnused
     *
     * @return bool
     */
    public function isUploaded(): bool
    {
        return $this->state === self::STATE_UPLOADED;
    }
}
