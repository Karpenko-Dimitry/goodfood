<?php

namespace App\Ai\Providers;

use App\Ai\AiResponseException;
use App\Ai\Contracts\StructuredTask;
use App\Ai\Contracts\TextModel;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use OpenAI\Contracts\ClientContract;

/**
 * Structured output through OpenAI Chat Completions (openai-php/client) with a strict JSON schema.
 */
class OpenAiTextModel implements TextModel
{
    public function __construct(private ClientContract $client, private ?string $apiKey = null) {}

    public function generate(StructuredTask $task, string $model): array
    {
        // Fail with an actionable message instead of a bare 401/404 from the API.
        if (blank($this->apiKey)) {
            throw new AiResponseException('OPENAI_API_KEY is not set (.env), or the config cache / queue worker still has the old value: run `php artisan config:clear` and restart the worker.');
        }

        if (str_starts_with($model, 'claude-')) {
            throw new AiResponseException("\"{$model}\" is not an OpenAI model: set AI_DIET_MODEL / AI_DISH_MODEL to e.g. gpt-4.1-mini.");
        }

        $schema = JsonSchema::object(fn ($s) => $task->schema($s))->withoutAdditionalProperties()->toArray();

        $response = $this->client->chat()->create([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $task->instructions()],
                ['role' => 'user', 'content' => $task->input()],
            ],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => Str::snake(class_basename($task)),
                    'strict' => true,
                    'schema' => $schema,
                ],
            ],
            'max_completion_tokens' => $task->maxTokens(),
        ]);

        $choice = $response->choices[0] ?? throw new AiResponseException('OpenAI returned no choices.');

        if ($choice->finishReason === 'length') {
            throw new AiResponseException("Output hit max_completion_tokens ({$task->maxTokens()}); raise it for ".class_basename($task).'.');
        }

        if ($choice->finishReason === 'content_filter') {
            throw new AiResponseException('OpenAI declined the request (content filter).');
        }

        $data = json_decode((string) $choice->message->content, true);

        return is_array($data) ? $data : throw new AiResponseException('OpenAI returned no valid JSON.');
    }
}
