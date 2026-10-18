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
    public function __construct(private ?string $apiKey, private string $quality = 'medium') {}

    public function available(): bool
    {
        return filled($this->apiKey);
    }

    public function generate(string $prompt, string $model): array
    {
        $params = ['model' => $model, 'prompt' => $prompt, 'size' => '1536x1024', 'n' => 1];

        // gpt-image-*: medium quality is several times faster and cheaper than high, and presets are ≤1440px anyway;
        // JPEG keeps the response small (it is converted to WebP presets afterwards).
        if (str_starts_with($model, 'gpt-image')) {
            $params += ['quality' => $this->quality, 'output_format' => 'jpeg'];
        }

        $response = OpenAI::client((string) $this->apiKey)->images()->create($params);

        $image = $response->data[0] ?? throw new AiResponseException('No image returned.');

        if ($image->b64_json !== '') {
            return [base64_decode($image->b64_json), ($params['output_format'] ?? 'png') === 'jpeg' ? 'jpg' : 'png'];
        }

        return [Http::timeout(60)->get($image->url)->throw()->body(), 'png'];
    }
}
