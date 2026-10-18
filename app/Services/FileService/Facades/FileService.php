<?php

namespace App\Services\FileService\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class IdentityService.
 * @package App\Services\Forus\Identities\Facades
 */
class FileService extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'file_service';
    }
}
