<?php

namespace App\Services\FileService\Events;

use App\Services\FileService\Models\FileObject;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired by HasFileObjects when a file object is deleted to/from a model.
 */
class FileObjectDeletedEvent
{
    use Dispatchable;

    public function __construct(public FileObject $fileObject) {}
}
