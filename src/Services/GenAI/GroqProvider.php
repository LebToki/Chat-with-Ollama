<?php

namespace App\Services\GenAI;

use GuzzleHttp\Client;
use Exception;

class GroqProvider implements GenAIProviderInterface
{
    private $apiKey;
    private $client;
    private $baseUrl = 'https://api.groq.com/openai/v1/';

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? $_ENV['GROQ_API_KEY'] ?? '';
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
            throw new Exception('Groq API key not configured');
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
                    'stream' => false,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            return [
                'response' => $data['choices'][0]['message']['content'] ?? '',
                'model_used' => $model,
                'provider' => 'groq',
            ];
        } catch (Exception $e) {
            throw new Exception('Groq API error: ' . $e->getMessage());
        }
    }

    public function stream(string $prompt, string $model, array $options = [], callable $callback = null): void
    {
        if (!$this->isAvailable()) {
            throw new Exception('Groq API key not configured');
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

                $data = substr($line, 6); // Remove 'data: ' prefix
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
            throw new Exception('Groq streaming error: ' . $e->getMessage());
        }
    }

    public function getModels(): array
    {
        // Groq free tier models
        return [
            'llama-3.1-8b-instant',
            'llama-3.1-70b-versatile',
            'mixtral-8x7b-32768',
            'gemma2-9b-it',
        ];
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    public function getName(): string
    {
        return 'Groq';
    }
}
