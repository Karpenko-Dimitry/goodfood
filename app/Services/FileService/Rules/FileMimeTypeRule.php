<?php

namespace App\Services\FileService\Rules;

use Closure;
use finfo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Request;

class FileMimeTypeRule implements ValidationRule
{
    /**
     * @var array list allowed mime types
     */
    protected array $mimeTypes = [];

    /**
     * @var Request|null
     */
    protected ?Request $request = null;

    /**
     * Create a new rule instance.
     *
     * @param string|array $mimeTypes
     * @param Request|null $request
     */
    public function __construct(
        array|string $mimeTypes,
        ?Request $request = null
    ) {
        $this->mimeTypes = (array) $mimeTypes;
        $this->request = $request ?? request();
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $file = request()->file($attribute);
        $finfo = new finfo(FILEINFO_MIME_TYPE);

        if ($file && !in_array($finfo->file($file->getRealPath()), $this->mimeTypes)) {
            $fail(__('validation.custom.file_object.type'));
        }
    }
}
