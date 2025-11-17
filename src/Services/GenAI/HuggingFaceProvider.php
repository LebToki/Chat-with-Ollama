<?php

namespace App\Services\GenAI;

use GuzzleHttp\Client;
use Exception;

class HuggingFaceProvider implements GenAIProviderInterface
{
    private $apiKey;
    private $client;
    private $baseUrl = 'https://api-inference.huggingface.co/models/';

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? $_ENV['HUGGINGFACE_API_KEY'] ?? '';
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 120.0,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function generate(string $prompt, string $model, array $options = []): array
    {
        if (!$this->isAvailable()) {
            throw new Exception('Hugging Face API key not configured');
        }

        try {
            // Hugging Face uses model name in URL
            $response = $this->client->post($model, [
                'json' => [
                    'inputs' => $prompt,
                    'parameters' => [
                        'max_new_tokens' => $options['max_tokens'] ?? 500,
                        'temperature' => $options['temperature'] ?? 0.7,
                        'return_full_text' => false,
                    ],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            // Handle different response formats
            $responseText = '';
            if (isset($data[0]['generated_text'])) {
                $responseText = $data[0]['generated_text'];
            } elseif (isset($data['generated_text'])) {
                $responseText = $data['generated_text'];
            } elseif (is_string($data)) {
                $responseText = $data;
            }
            
            return [
                'response' => $responseText,
                'model_used' => $model,
                'provider' => 'huggingface',
            ];
        } catch (Exception $e) {
            throw new Exception('Hugging Face API error: ' . $e->getMessage());
        }
    }

    public function stream(string $prompt, string $model, array $options = [], callable $callback = null): void
    {
        // Hugging Face doesn't support streaming in the same way, so we'll simulate it
        $result = $this->generate($prompt, $model, $options);
        $response = $result['response'];
        
        // Simulate streaming by chunking the response
        $chunks = str_split($response, 10);
        foreach ($chunks as $index => $chunk) {
            if ($callback) {
                $callback($chunk, $index === count($chunks) - 1);
            }
            usleep(50000); // Small delay to simulate streaming
        }
    }

    public function getModels(): array
    {
        // Popular free Hugging Face models
        return [
            'mistralai/Mistral-7B-Instruct-v0.2',
            'meta-llama/Llama-2-7b-chat-hf',
            'google/flan-t5-large',
            'microsoft/DialoGPT-large',
            'tiiuae/falcon-7b-instruct',
        ];
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    public function getName(): string
    {
        return 'Hugging Face';
    }
}
