<?php

namespace App\Ai\Contracts;

interface TextModel
{
    /**
     * Run the task and return the decoded JSON answer.
     *
     * @throws \App\Ai\AiResponseException
     */
    public function generate(StructuredTask $task, string $model): array;
}
