<?php

namespace App\Services\GenAI;

use GuzzleHttp\Client;
use Exception;

class OpenRouterProvider implements GenAIProviderInterface
{
    private $apiKey;
    private $client;
    private $baseUrl = 'https://openrouter.ai/api/v1/';

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? $_ENV['OPENROUTER_API_KEY'] ?? '';
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 120.0,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => $_ENV['APP_URL'] ?? 'http://localhost',
            ],
        ]);
    }

    public function generate(string $prompt, string $model, array $options = []): array
    {
        if (!$this->isAvailable()) {
            throw new Exception('OpenRouter API key not configured');
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
                'provider' => 'openrouter',
            ];
        } catch (Exception $e) {
            throw new Exception('OpenRouter API error: ' . $e->getMessage());
        }
    }

    public function stream(string $prompt, string $model, array $options = [], callable $callback = null): void
    {
        if (!$this->isAvailable()) {
            throw new Exception('OpenRouter API key not configured');
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
            throw new Exception('OpenRouter streaming error: ' . $e->getMessage());
        }
    }

    public function getModels(): array
    {
        // OpenRouter free tier models (some require credits but many are free)
        return [
            'google/gemini-flash-1.5',
            'google/gemini-pro',
            'mistralai/mistral-7b-instruct',
            'meta-llama/llama-3-8b-instruct',
            'qwen/qwen-2.5-7b-instruct',
        ];
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    public function getName(): string
    {
        return 'OpenRouter';
    }
}
