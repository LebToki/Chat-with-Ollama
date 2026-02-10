<?php

namespace App\Services\GenAI;

use GuzzleHttp\Client;
use Exception;

class DALLEProvider implements ImageGenerationProviderInterface
{
    private $apiKey;
    private $apiUrl;
    private $client;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? $_ENV['OPENAI_API_KEY'] ?? '';
        $this->apiUrl = 'https://api.openai.com/v1/';
        
        if (empty($this->apiKey)) {
            throw new Exception('OpenAI API key is required for DALL-E');
        }

        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'timeout' => 300.0,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->apiKey,
            ],
        ]);
    }

    public function generateImage(string $prompt, array $options = []): array
    {
        try {
            $model = $options['model'] ?? 'dall-e-3';
            $size = $options['size'] ?? '1024x1024';
            $quality = $options['quality'] ?? 'standard';
            $n = $options['n'] ?? 1;

            $response = $this->client->post('images/generations', [
                'json' => [
                    'model' => $model,
                    'prompt' => $prompt,
                    'n' => $n,
                    'size' => $size,
                    'quality' => $quality,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            $images = [];
            if (isset($data['data'])) {
                foreach ($data['data'] as $imageData) {
                    $images[] = [
                        'url' => $imageData['url'] ?? null,
                        'revised_prompt' => $imageData['revised_prompt'] ?? null,
                    ];
                }
            }
            
            return [
                'images' => $images,
                'model_used' => $model,
                'provider' => 'dalle',
            ];
        } catch (Exception $e) {
            throw new Exception('DALL-E API error: ' . $e->getMessage());
        }
    }

    public function getModels(): array
    {
        return ['dall-e-3', 'dall-e-2'];
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    public function getName(): string
    {
        return 'DALL-E';
    }
}
