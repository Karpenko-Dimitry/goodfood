<?php

namespace App\Services\FileService;

use App\Services\FileService\Exceptions\FileSourceAlreadyExistsException;
use App\Services\FileService\Models\FileObject;

/**
 * Class AbstractFileConfig.
 * @package App\Services\FileServices\Configs
 */
abstract class AbstractFileConfig
{
    public const GROUP_NAME = 'file';
    public const DEFAULT_CONFIG_PREFIX = 'model_';

    public const TYPE_SINGLE = 'single';
    public const TYPE_MULTIPLE = 'multiple';

    public const TYPES = [
        self::TYPE_SINGLE,
        self::TYPE_MULTIPLE,
    ];

    protected array $fileSources = [];

    /**
     * @var array
     */
    protected array $extensions = [];

    protected array $not_valid_extensions = [
        'stl', 'ply',
    ];

    /**
     * @var array
     */
    protected array $mime_types = [];

    /**
     * @var int
     */
    protected int $max_file_size = 2600000;

    /**
     * @var string
     */
    protected string $regenerate_type = 'original';

    /**
     * @var string|null Reference name for a file
     */
    protected ?string $name = null;

    /**
     * @var string
     */
    protected string $type = self::TYPE_SINGLE;

    /**
     * @var bool
     */
    protected bool $use_queue = false;

    /**
     * @var array
     */
    protected array $sync_files = [
        'thumbnail',
    ];

    /**
     * @var bool
     */
    protected bool $save_dominant_color = false;

    /**
     * @var bool
     */
    protected bool $should_defer_uploading = false;

    /**
     * @return string
     */
    public static function getDefaultConfigName(): string
    {
        return self::DEFAULT_CONFIG_PREFIX . self::GROUP_NAME;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name ?? null;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type ?? null;
    }

    /**
     * @return AbstractFileSource[]
     */
    public function getFileSources(): array
    {
        return $this->fileSources ?? [];
    }

    /**
     * @return string
     */
    public function getRegenerationTypeName(): string
    {
        return $this->regenerate_type;
    }

    /**
     * @return string[]
     */
    public function getExtensions(): array
    {
        return $this->extensions ?? [];
    }

    /**
     * @noinspection PhpUnused
     * @return array|string[]
     */
    public function getNotValidExtensions(): array
    {
        return $this->not_valid_extensions ?? [];
    }

    /**
     * @return string[]
     */
    public function getMimeTypes(): array
    {
        return $this->mime_types ?? [];
    }

    /**
     * @noinspection PhpUnused
     * @param int $default
     * @return int
     */
    public function getMaxFileSize(int $default = 1024): int
    {
        return $this->max_file_size ?: $default;
    }

    /**
     * @return bool
     */
    public function useQueue(): bool
    {
        return $this->use_queue;
    }

    /**
     * @return bool
     */
    public function shouldDeferUploading(): bool
    {
        return $this->should_defer_uploading;
    }

    /**
     * @return array
     */
    public function getSyncFiles(): array
    {
        return $this->regenerate_type ? array_merge(
            $this->sync_files,
            (array) $this->regenerate_type
        ) : $this->sync_files;
    }

    /**
     * @param FileObject $fileObject
     * @param bool $fromQueue
     * @return void
     */
    abstract public function onFilesUpdated(FileObject $fileObject, bool $fromQueue = false): void;

    /**
     * @param AbstractFileSource $fileSource
     * @param bool $overwriteIfExists
     * @throws FileSourceAlreadyExistsException
     * @return AbstractFileSource
     */
    protected function addFileSource(
        AbstractFileSource $fileSource,
        bool $overwriteIfExists = false
    ): AbstractFileSource {
        if (!$overwriteIfExists && isset($this->fileSources[$fileSource->name])) {
            throw new FileSourceAlreadyExistsException(sprintf(
                "File config '%s' already has '%s' file source.",
                $this->getName(),
                $fileSource->name
            ));
        }

        return $this->fileSources[$fileSource->name] = $fileSource;
    }
}
