<?php

namespace App\Ai\Contracts;

use Illuminate\Contracts\JsonSchema\JsonSchema;

/**
 * One prompt whose answer must be JSON matching schema().
 */
interface StructuredTask
{
    /** System prompt. */
    public function instructions(): string;

    /** User message. */
    public function input(): string;

    /**
     * Properties of the root object, built with Laravel's JSON schema builder.
     *
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array;

    public function maxTokens(): int;
}
