<?php

namespace App\Services\FileService\Traits;

use App\Services\FileService\Events\FileObjectAttachedEvent;
use App\Services\FileService\Events\FileObjectDeletedEvent;
use App\Services\FileService\FileService;
use App\Services\FileService\Models\FileObject;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Trait HasFiles.
 *
 * @property Collection $fileObjects
 */
trait HasFileObjects
{
    /**
     * @param FileObject $fileObject
     * @return static
     */
    public function attachFileObject(FileObject $fileObject): static
    {
        $this->syncFileObjects($fileObject->uuid, $fileObject->type);

        return $this;
    }

    /**
     * @param array $uids
     */
    public function attachFileObjectsWithoutUnlink(array $uids): void
    {
        foreach ($uids as $order => $uuid) {
            $fileObject = FileObject::findByUuid($uuid);

            $fileObject?->update([
                'order' => $order,
                'fileable_type' => $this->getMorphClass(),
                'fileable_id' => $this->id,
            ]);

            $fileObject && event(new FileObjectAttachedEvent($fileObject));
        }
    }

    /**
     * @param $uids
     * @param string $fileConfigType
     * @return bool
     */
    public function syncFileObjects($uids, string $fileConfigType): bool
    {
        $uids = (array) $uids;
        $fileConfig = FileService::getFileConfig($fileConfigType);
        $multiple = $fileConfig && ($fileConfig->getType() === $fileConfig::TYPE_MULTIPLE);

        if (!$fileConfig || (!$multiple && count($uids) > 1)) {
            return false;
        }

        // remove old file objects
        $oldUids = $this->fileObjects()->where([
            'type' => $fileConfig->getName(),
        ])->whereNotIn('uuid', $uids)->pluck('uuid')->toArray();

        $this->unlinkFileObjects($oldUids);

        // attach new file objects
        /** @var FileObject[] $newFileObjects */
        $newFileObjects = FileObject::where('type', $fileConfig->getName())
            ->whereIn('uuid', $uids)->get();

        $order = array_flip(array_diff($uids, $oldUids));

        foreach ($newFileObjects as $newFileObject) {
            $newFileObject->update([
                'order' => $order[$newFileObject->uuid],
                'fileable_type' => $this->getMorphClass(),
                'fileable_id' => $this->id,
            ]);

            event(new FileObjectAttachedEvent($newFileObject));
        }

        return true;
    }

    /**
     * Remove files by uuid.
     *
     * @param array|string $uids
     */
    public function unlinkFileObjects(array|string $uids): void
    {
        $fileObjects = $this->fileObjects()->whereIn('uuid', (array) $uids)->get();

        try {
            $fileObjects->each(function (FileObject $fileObject) {
                event(new FileObjectDeletedEvent($fileObject));
                resolve('file_service')->unlink($fileObject);
            });
        } catch (Exception $exception) {
            logger()->error($exception->getMessage());
        }
    }

    /**
     * Get all file objects.
     *
     * @return MorphMany
     */
    public function fileObjects(): MorphMany
    {
        return $this->morphMany(FileObject::class, 'fileable');
    }
}
