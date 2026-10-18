<?php

namespace App\Ai\Contracts;

interface ImageModel
{
    public function available(): bool;

    /**
     * Generate one landscape image.
     *
     * @return array{0: string, 1: string} binary content and file extension
     */
    public function generate(string $prompt, string $model): array;
}
