<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;

trait HasImage
{
    /**
     * An uploaded image (public disk) wins over an external image URL.
     */
    public function imageUrl(): ?string
    {
        if ($this->image) {
            return Storage::disk('public')->url($this->image);
        }

        return $this->image_url ?: null;
    }
}
