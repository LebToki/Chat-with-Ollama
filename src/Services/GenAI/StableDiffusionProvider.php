<?php

namespace App\Services\GenAI;

use GuzzleHttp\Client;
use Exception;

class StableDiffusionProvider implements ImageGenerationProviderInterface
{
    private $apiKey;
    private $apiUrl;
    private $client;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? $_ENV['STABILITY_API_KEY'] ?? '';
        $this->apiUrl = 'https://api.stability.ai/v1/';
        
        if (empty($this->apiKey)) {
            throw new Exception('Stability AI API key is required for Stable Diffusion');
        }

        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'timeout' => 300.0,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ],
        ]);
    }

    public function generateImage(string $prompt, array $options = []): array
    {
        try {
            $engine = $options['engine'] ?? 'stable-diffusion-xl-1024-v1-0';
            $width = $options['width'] ?? 1024;
            $height = $options['height'] ?? 1024;
            $steps = $options['steps'] ?? 30;
            $cfg_scale = $options['cfg_scale'] ?? 7;
            $samples = $options['samples'] ?? 1;

            $response = $this->client->post("generation/{$engine}/text-to-image", [
                'json' => [
                    'text_prompts' => [
                        ['text' => $prompt, 'weight' => 1]
                    ],
                    'cfg_scale' => $cfg_scale,
                    'height' => $height,
                    'width' => $width,
                    'steps' => $steps,
                    'samples' => $samples,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            $images = [];
            if (isset($data['artifacts'])) {
                foreach ($data['artifacts'] as $artifact) {
                    $images[] = [
                        'base64' => $artifact['base64'] ?? null,
                        'seed' => $artifact['seed'] ?? null,
                        'finishReason' => $artifact['finishReason'] ?? null,
                    ];
                }
            }
            
            return [
                'images' => $images,
                'model_used' => $engine,
                'provider' => 'stable-diffusion',
            ];
        } catch (Exception $e) {
            throw new Exception('Stable Diffusion API error: ' . $e->getMessage());
        }
    }

    public function getModels(): array
    {
        return [
            'stable-diffusion-xl-1024-v1-0',
            'stable-diffusion-v1-6',
            'stable-diffusion-xl-1024-v0-9',
        ];
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    public function getName(): string
    {
        return 'Stable Diffusion';
    }
}
