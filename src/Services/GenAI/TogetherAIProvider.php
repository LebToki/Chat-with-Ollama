<?php

namespace App\Services\GenAI;

use GuzzleHttp\Client;
use Exception;

class TogetherAIProvider implements GenAIProviderInterface
{
    private $apiKey;
    private $client;
    private $baseUrl = 'https://api.together.xyz/v1/';

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? $_ENV['TOGETHER_API_KEY'] ?? '';
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
            throw new Exception('Together AI API key not configured');
        }

        try {
            $response = $this->client->post('chat/completions', [
                'json' => [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => $options['temperature'] ?? 0.7,
                    'max_tokens' => $options['max_tokens'] ?? 2048,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            return [
                'response' => $data['choices'][0]['message']['content'] ?? '',
                'model_used' => $model,
                'provider' => 'togetherai',
            ];
        } catch (Exception $e) {
            throw new Exception('Together AI API error: ' . $e->getMessage());
        }
    }

    public function stream(string $prompt, string $model, array $options = [], callable $callback = null): void
    {
        if (!$this->isAvailable()) {
            throw new Exception('Together AI API key not configured');
        }

        try {
            $response = $this->client->post('chat/completions', [
                'json' => [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => $options['temperature'] ?? 0.7,
                    'max_tokens' => $options['max_tokens'] ?? 2048,
                    'stream' => true,
                ],
                'stream' => true,
            ]);

            $stream = $response->getBody();
            $fullResponse = '';

            while (!$stream->eof()) {
                $line = $stream->readLine();
                if (empty(trim($line)) || !str_starts_with($line, 'data: ')) {
                    continue;
                }

                $data = substr($line, 6);
                if (trim($data) === '[DONE]') {
                    if ($callback) {
                        $callback('', true);
                    }
                    break;
                }

                $json = json_decode($data, true);
                if (isset($json['choices'][0]['delta']['content'])) {
                    $chunk = $json['choices'][0]['delta']['content'];
                    $fullResponse .= $chunk;
                    
                    if ($callback) {
                        $callback($chunk, false);
                    }
                }
            }
        } catch (Exception $e) {
            throw new Exception('Together AI streaming error: ' . $e->getMessage());
        }
    }

    public function getModels(): array
    {
        // Together AI free tier models
        return [
            'meta-llama/Llama-3-8b-chat-hf',
            'mistralai/Mixtral-8x7B-Instruct-v0.1',
            'meta-llama/Llama-3-70b-chat-hf',
            'Qwen/Qwen2.5-7B-Instruct',
        ];
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    public function getName(): string
    {
        return 'Together AI';
    }
}
