<?php

namespace App\Ai\Providers;

use Anthropic\Client;
use App\Ai\AiResponseException;
use App\Ai\Contracts\StructuredTask;
use App\Ai\Contracts\TextModel;
use Illuminate\JsonSchema\JsonSchema;

/**
 * Structured output through the official Anthropic PHP SDK (anthropic-ai/sdk).
 */
class ClaudeTextModel implements TextModel
{
    public function __construct(private Client $client, private ?string $effort = null) {}

    public function generate(StructuredTask $task, string $model): array
    {
        $schema = JsonSchema::object(fn ($s) => $task->schema($s))->withoutAdditionalProperties()->toArray();

        $message = $this->client->beta->messages->create(
            maxTokens: $task->maxTokens(),
            messages: [['role' => 'user', 'content' => $task->input()]],
            model: $model,
            system: $task->instructions(),
            outputConfig: array_filter([
                'effort' => $this->effort,
                'format' => ['type' => 'json_schema', 'schema' => $schema],
            ]),
            // On a policy decline the API retries on the model's default fallback within the same call.
            fallbacks: 'default',
            betas: ['server-side-fallback-2026-07-01'],
        );

        if ($message->stopReason === 'refusal') {
            throw new AiResponseException('The model declined the request: '.($message->stopDetails?->explanation ?? 'no details'));
        }

        if ($message->stopReason === 'max_tokens') {
            throw new AiResponseException("Output hit max_tokens ({$task->maxTokens()}); raise it for ".class_basename($task).'.');
        }

        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $data = json_decode($block->text, true);

                if (is_array($data)) {
                    return $data;
                }
            }
        }

        throw new AiResponseException('The model returned no valid JSON.');
    }
}
