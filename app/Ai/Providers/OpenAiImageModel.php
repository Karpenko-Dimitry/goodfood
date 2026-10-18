<?php

namespace App\Ai\Providers;

use App\Ai\AiResponseException;
use App\Ai\Contracts\ImageModel;
use Illuminate\Support\Facades\Http;
use OpenAI;

/**
 * Photo generation through openai-php/client (Claude does not generate images).
 */
class OpenAiImageModel implements ImageModel
{
    public function __construct(private ?string $apiKey) {}

    public function available(): bool
    {
        return filled($this->apiKey);
    }

    public function generate(string $prompt, string $model): array
    {
        $response = OpenAI::client((string) $this->apiKey)->images()->create([
            'model' => $model,
            'prompt' => $prompt,
            'size' => '1536x1024',
            'n' => 1,
        ]);

        $image = $response->data[0] ?? throw new AiResponseException('No image returned.');

        if ($image->b64_json !== '') {
            return [base64_decode($image->b64_json), 'png'];
        }

        return [Http::timeout(60)->get($image->url)->throw()->body(), 'png'];
    }
}
